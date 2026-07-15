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
        if (empty($walletdid)) {
            $walletmanager = new ticket_manager();
            $walletdid = $walletmanager->get_did($user->id);
        }

        $lock = $this->get_issue_lock($blerifyrecord->id, $user->id);
        try {
            $credrecord = $this->get_or_create_record($blerifyrecord->id, $user->id, $walletdid);

            if ($credrecord->status === 'assembled') {
                return $credrecord;
            }

            $this->process_issuance($credrecord, $config, $user, $walletdid);

            return $credrecord;
        } finally {
            $lock->release();
        }
    }

    private function get_issue_lock($blerifyid, $userid) {
        $factory = \core\lock\lock_config::get_lock_factory('mod_blerify_issuance');
        $lock = $factory->get_lock($blerifyid . '_' . $userid, 15);
        if (!$lock) {
            throw new \Exception('Blerify: could not acquire issuance lock for user ' . $userid);
        }
        return $lock;
    }

    private function get_or_create_record($blerifyid, $userid, $walletdid = null) {
        global $DB;

        $now = time();
        $credrecord = new \stdClass();
        $credrecord->blerifyid = $blerifyid;
        $credrecord->userid = $userid;
        $credrecord->wallet_did = $walletdid;
        $credrecord->status = 'pending';
        $credrecord->laststep = null;
        $credrecord->timecreated = $now;
        $credrecord->timemodified = $now;

        try {
            $credrecord->id = $DB->insert_record('blerify_credentials', $credrecord);
            return $credrecord;
        } catch (\dml_write_exception $e) {
            return $DB->get_record('blerify_credentials',
                ['blerifyid' => $blerifyid, 'userid' => $userid], '*', MUST_EXIST);
        }
    }

    private function process_issuance($credrecord, $config, $user, $walletdid) {
        global $DB;

        $resume = null;
        if (!empty($credrecord->credentialid) && !empty($credrecord->laststep)
                && $credrecord->laststep !== 'assembled') {
            $resume = [
                'credentialid' => $credrecord->credentialid,
                'laststep' => $credrecord->laststep,
                'signingmessage' => $credrecord->signingmessage ?? null,
                'signature' => $credrecord->signature ?? null,
                'publickey' => $credrecord->publickey ?? null,
            ];
        }

        try {
            $api = new apirest(new client());
            $result = $api->issue_credential(
                $user,
                $config->templateid,
                $config->projectid,
                $walletdid,
                $resume
            );

            $credrecord->credentialid = $result['credential_id'];
            $credrecord->status = $result['status'];
            $credrecord->laststep = 'assembled';
            $credrecord->wallet_did = $walletdid;
            $credrecord->signingmessage = null;
            $credrecord->signature = null;
            $credrecord->publickey = null;
            $credrecord->errordetail = null;
            $credrecord->timemodified = time();

            if (!empty($result['assemble_raw'])) {
                $credrecord->deeplinkurl = $result['assemble_raw'];
            }

            $DB->update_record('blerify_credentials', $credrecord);

            return $result;

        } catch (\Exception $e) {
            $credrecord->status = 'error';
            if ($e instanceof \mod_blerify\apirest\issuance_exception) {
                if (!empty($e->credentialid)) {
                    $credrecord->credentialid = $e->credentialid;
                }
                if (!empty($e->laststep)) {
                    $credrecord->laststep = $e->laststep;
                }
                $credrecord->signingmessage = $e->signingmessage;
                $credrecord->signature = $e->signature;
                $credrecord->publickey = $e->publickey;
            }
            $credrecord->errordetail = 'issuance_failed';
            $credrecord->timemodified = time();
            $DB->update_record('blerify_credentials', $credrecord);
            debugging('Blerify issuance failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw $e;
        }
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
        $lock = $this->get_issue_lock($blerifyrecord->id, $user->id);
        try {
            $credrecord = $this->get_or_create_record($blerifyrecord->id, $user->id, $walletdid);
            $credrecord->wallet_did = $walletdid;

            $result = $this->process_issuance($credrecord, $config, $user, $walletdid);

            return [
                'credential_id' => $result['credential_id'],
                'assemble_raw' => $result['assemble_raw'] ?? '',
            ];
        } finally {
            $lock->release();
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

        $lock = $this->get_issue_lock($blerifyrecord->id, $user->id);
        try {
            $existing = $DB->get_record('blerify_credentials', ['id' => $existing->id], '*', MUST_EXIST);
            $existing->wallet_did = $walletdid;

            if ($existing->status === 'assembled') {
                $existing->credentialid = null;
                $existing->laststep = null;
                $existing->signingmessage = null;
                $existing->signature = null;
                $existing->publickey = null;
            }

            $existing->status = 'pending';
            $existing->timemodified = time();
            $DB->update_record('blerify_credentials', $existing);

            $result = $this->process_issuance($existing, $config, $user, $walletdid);

            return [
                'credential_id' => $result['credential_id'],
                'assemble_raw' => $result['assemble_raw'] ?? '',
            ];
        } finally {
            $lock->release();
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
        $sa = \mod_blerify\local\service_account::get_decoded();
        if ($sa && isset($sa['token_uri'])) {
            $host = parse_url($sa['token_uri'], PHP_URL_HOST);
            if (strpos($host, 'api.demo.') !== false) {
                return 'demo';
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
}
