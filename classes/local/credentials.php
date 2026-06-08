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
 * Credentials management for mod_blerify.
 * Handles issuing, storing, and retrieving credential records.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_blerify\local;

defined('MOODLE_INTERNAL') || die();

use mod_blerify\client\client;
use mod_blerify\apirest\apirest;
use mod_blerify\wallet\ticket_manager;

class credentials {

    /**
     * Issue a W3C credential for a user in a blerify activity.
     *
     * @param object $blerifyrecord The blerify activity record.
     * @param object $config The blerify_configs record.
     * @param object $user The Moodle user object.
     * @param string|null $walletdid Optional wallet DID override.
     * @return object The blerify_credentials DB record.
     * @throws \Exception
     */
    public function issue_credential($blerifyrecord, $config, $user, $walletdid = null) {
        global $DB;

        $now = time();

        $credrecord = new \stdClass();
        $credrecord->blerifyid = $blerifyrecord->id;
        $credrecord->userid = $user->id;
        $credrecord->status = 'pending';
        $credrecord->timecreated = $now;
        $credrecord->timemodified = $now;
        $credrecord->id = $DB->insert_record('blerify_credentials', $credrecord);

        try {
            $blerify = new client();

            $api = new apirest($blerify);

            if (empty($walletdid)) {
                $walletmanager = new ticket_manager();
                $walletdid = $walletmanager->get_did($user->id);
            }

            $result = $api->issue_credential(
                $user,
                $config->templateid,
                $config->projectid,
                $walletdid
            );

            $credrecord->credentialid = $result['credential_id'];
            $credrecord->status = $result['status'];
            $credrecord->wallet_did = $walletdid;
            $credrecord->timemodified = time();

            if (!empty($result['assemble_raw'])) {
                $credrecord->deeplinkurl = $result['assemble_raw'];
            }

            $DB->update_record('blerify_credentials', $credrecord);

        } catch (\Exception $e) {
            $credrecord->status = 'error';
            $credrecord->errordetail = $e->getMessage();
            $credrecord->timemodified = time();
            $DB->update_record('blerify_credentials', $credrecord);
            throw $e;
        }

        return $credrecord;
    }

    /**
     * Issue a credential and return the assemble data directly.
     *
     * @param object $blerifyrecord The blerify activity record.
     * @param object $config The blerify_configs record.
     * @param object $user The Moodle user object.
     * @param string $walletdid The wallet DID.
     * @return array {credential_id, assemble_data}
     * @throws \Exception
     */
    public function issue_credential_w3c($blerifyrecord, $config, $user, $walletdid) {
        global $DB;

        $now = time();

        $credrecord = new \stdClass();
        $credrecord->blerifyid = $blerifyrecord->id;
        $credrecord->userid = $user->id;
        $credrecord->wallet_did = $walletdid;
        $credrecord->status = 'pending';
        $credrecord->timecreated = $now;
        $credrecord->timemodified = $now;
        $credrecord->id = $DB->insert_record('blerify_credentials', $credrecord);

        try {
            $blerify = new client();
            $api = new apirest($blerify);

            $result = $api->issue_credential(
                $user,
                $config->templateid,
                $config->projectid,
                $walletdid
            );

            $credrecord->credentialid = $result['credential_id'];
            $credrecord->status = $result['status'];
            $credrecord->timemodified = time();

            $assembleraw = $result['assemble_raw'] ?? '';
            if (!empty($assembleraw)) {
                $credrecord->deeplinkurl = $assembleraw;
            }

            $DB->update_record('blerify_credentials', $credrecord);

            return [
                'credential_id' => $result['credential_id'],
                'assemble_raw' => $assembleraw,
            ];

        } catch (\Exception $e) {
            $credrecord->status = 'error';
            $credrecord->errordetail = $e->getMessage();
            $credrecord->timemodified = time();
            $DB->update_record('blerify_credentials', $credrecord);
            throw $e;
        }
    }

    /**
     * Re-issue a credential for a user that already has one.
     * Calls the API to create a new credential and updates the existing DB record.
     *
     * @param object $blerifyrecord The blerify activity record.
     * @param object $config The blerify_configs record.
     * @param object $user The Moodle user object.
     * @param string $walletdid The wallet DID.
     * @param object $existing The existing blerify_credentials record.
     * @return array {credential_id, assemble_data}
     * @throws \Exception
     */
    public function reissue_credential_w3c($blerifyrecord, $config, $user, $walletdid, $existing) {
        global $DB;

        $existing->status = 'pending';
        $existing->timemodified = time();
        $DB->update_record('blerify_credentials', $existing);

        try {
            $blerify = new client();
            $api = new apirest($blerify);

            $result = $api->issue_credential(
                $user,
                $config->templateid,
                $config->projectid,
                $walletdid
            );

            $existing->credentialid = $result['credential_id'];
            $existing->status = $result['status'];
            $existing->wallet_did = $walletdid;
            $existing->timemodified = time();

            $assembleraw = $result['assemble_raw'] ?? '';
            if (!empty($assembleraw)) {
                $existing->deeplinkurl = $assembleraw;
            }

            $DB->update_record('blerify_credentials', $existing);

            return [
                'credential_id' => $result['credential_id'],
                'assemble_raw' => $assembleraw,
            ];

        } catch (\Exception $e) {
            $existing->status = 'error';
            $existing->errordetail = $e->getMessage();
            $existing->timemodified = time();
            $DB->update_record('blerify_credentials', $existing);
            throw $e;
        }
    }

    /**
     * Get credential record for a specific user in a blerify activity.
     *
     * @param int $blerifyid
     * @param int $userid
     * @return object|false
     */
    public function get_credential_for_user($blerifyid, $userid) {
        global $DB;

        return $DB->get_record('blerify_credentials', [
            'blerifyid' => $blerifyid,
            'userid' => $userid,
        ]);
    }

    /**
     * Get all credentials for a blerify activity, joined with user data.
     *
     * @param int $blerifyid
     * @return array
     */
    public function get_all_credentials($blerifyid) {
        global $DB;

        $sql = "SELECT bc.*, u.firstname, u.lastname, u.email
                  FROM {blerify_credentials} bc
                  JOIN {user} u ON u.id = bc.userid
                 WHERE bc.blerifyid = :blerifyid
                 ORDER BY bc.timecreated DESC";

        return $DB->get_records_sql($sql, ['blerifyid' => $blerifyid]);
    }

    /**
     * Detect environment from the service account token_uri.
     *
     * @return string 'demo' or 'production'.
     */
    public function get_environment(): string {
        $json = get_config('mod_blerify', 'service_account_json');
        if (!empty($json)) {
            $sa = json_decode($json, true);
            if ($sa && isset($sa['token_uri'])) {
                $host = parse_url($sa['token_uri'], PHP_URL_HOST);
                if (strpos($host, 'api.demo.') !== false) {
                    return 'demo';
                }
            }
        }
        return 'production';
    }

    /**
     * Get the wallet hostname for the current environment.
     *
     * @return string 'demo.wallet.blerify.com' or 'wallet.blerify.com'.
     */
    public function get_wallet_host(): string {
        if ($this->get_environment() === 'demo') {
            return 'demo.wallet.blerify.com';
        }
        return 'wallet.blerify.com';
    }

    /**
     * Build a deeplink URL for the wallet app.
     *
     * @param string $claimurl The walletclaim.php endpoint URL with token.
     * @return string The complete deeplink URL.
     */
    public function build_deeplink_v2(string $claimurl): string {
        $wallethost = $this->get_wallet_host();
        $env = $this->get_environment();

        return 'https://' . $wallethost . '/' . $env .
            '/downloadW3C?claim_mode=OTP&resource_link=' . urlencode($claimurl);
    }

    /**
     * Get the organization ID from service account JSON.
     *
     * @return string
     */
    private function get_organization_id() {
        $json = get_config('mod_blerify', 'service_account_json');
        if (!empty($json)) {
            $sa = json_decode($json, true);
            if ($sa && isset($sa['organization_id'])) {
                return $sa['organization_id'];
            }
        }
        return get_config('mod_blerify', 'organization_id');
    }
}
