<?php
// This file is part of the Blerify Certificate module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Blerify W3C credential API rest client.
 * Implements the 3-step W3C flow: Create -> Sign -> Assemble
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_blerify\apirest;

defined('MOODLE_INTERNAL') || die();

use mod_blerify\client\client;

class apirest {

    /** @var client */
    private $client;

    /** @var string */
    private $organizationid;

    /**
     * Constructor.
     *
     * @param client|null $client Blerify client (null creates default).
     */
    public function __construct($client = null) {
        $this->client = $client ? $client : new client();
        $this->organizationid = $this->client->get_organization_id();
    }

    /**
     * Issue a W3C credential through the 3-step flow.
     *
     * @param object $user Moodle user object.
     * @param string $templateid Blerify template UUID.
     * @param string $projectid Blerify project UUID.
     * @param string|null $walletdid Optional wallet DID.
     * @return array ['credential_id' => string, 'status' => string, 'code' => string|null]
     * @throws \Exception On API error.
     */
    public function issue_credential($user, $templateid, $projectid, $walletdid = null) {
        $createresponse = $this->create_credential($user, $templateid, $projectid, $walletdid);

        $credential = isset($createresponse->credential) ? $createresponse->credential : $createresponse;
        if (!isset($createresponse->signingMessage) || !isset($credential->_id)) {
            throw new \Exception('Failed to create credential: malformed response');
        }
        $signingmessage = $createresponse->signingMessage;
        $credentialid = $credential->_id;

        try {
            $signresult = $this->sign_credential($projectid, $credentialid, $signingmessage);
        } catch (\Exception $e) {
            throw new issuance_exception($e->getMessage(), $credentialid, 'created', $e);
        }

        try {
            $assembleresponse = $this->assemble_credential($projectid, $credentialid, $templateid,
                $signresult->signature, $signresult->publicKey);
        } catch (\Exception $e) {
            throw new issuance_exception($e->getMessage(), $credentialid, 'signed', $e);
        }

        return [
            'credential_id' => $credentialid,
            'status' => 'assembled',
            'code' => isset($credential->code) ? $credential->code : null,
            'assemble_raw' => $assembleresponse,
        ];
    }

    /**
     * Step 1: Create a W3C credential.
     *
     * @param object $user
     * @param string $templateid
     * @param string $projectid
     * @param string|null $walletdid
     * @return object API response.
     * @throws \Exception
     */
    private function create_credential($user, $templateid, $projectid, $walletdid = null) {
        $path = '/api/v1/organizations/' . rawurlencode($this->organizationid) .
                '/projects/' . rawurlencode($projectid) . '/credentials';

        if (empty($walletdid)) {
            throw new \Exception('Cannot issue credential: student has not linked a wallet DID.');
        }
        $did = $walletdid;

        $organizationuser = [
            'email' => $user->email,
            'did' => $did,
        ];

        $body = [
            'projectId' => $projectid,
            'templateId' => $templateid,
            'additionalData' => [
                'w3cData' => [
                    'email' => $user->email,
                    'fullname' => $user->firstname,
                    'lastname' => $user->lastname,
                ],
            ],
            'organizationUser' => $organizationuser,
            'options' => [
                'approvers' => false,
            ],
        ];

        $response = $this->client->post($path, $body);
        if (!$response) {
            throw new \Exception('Failed to create credential: empty response');
        }

        return $response;
    }

    /**
     * Step 2: Sign a credential.
     *
     * @param string $projectid
     * @param string $credentialid
     * @param string $signingmessage
     * @return object Response with signature and publicKey.
     * @throws \Exception
     */
    private function sign_credential($projectid, $credentialid, $signingmessage) {
        $path = '/api/v1/organizations/' . rawurlencode($this->organizationid) .
                '/projects/' . rawurlencode($projectid) .
                '/credentials/' . rawurlencode($credentialid) . '/sign/custody';

        $body = [
            'signingMessage' => $signingmessage,
        ];

        $response = $this->client->put($path, $body);
        if (!$response || !isset($response->signature) || !isset($response->publicKey)) {
            throw new \Exception('Failed to sign credential: invalid response');
        }

        return $response;
    }

    /**
     * Step 3: Assemble a credential.
     *
     * @param string $projectid
     * @param string $credentialid
     * @param string $templateid
     * @param string $signature
     * @param string $publickey
     * @return object Assembled W3C VerifiableCredential.
     * @throws \Exception
     */
    private function assemble_credential($projectid, $credentialid, $templateid, $signature, $publickey) {
        $path = '/api/v1/organizations/' . rawurlencode($this->organizationid) .
                '/projects/' . rawurlencode($projectid) .
                '/credentials/' . rawurlencode($credentialid) . '/assemble?keystore=keyvault';

        $body = [
            'templateId' => $templateid,
            'signature' => $signature,
            'publicKey' => $publickey,
        ];

        $response = $this->client->put_raw($path, $body);
        if (!$response) {
            throw new \Exception('Failed to assemble credential: invalid response');
        }

        return $response;
    }
}
