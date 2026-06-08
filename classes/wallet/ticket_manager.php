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
 * Ticket manager for wallet credential claiming via QR.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_blerify\wallet;

defined('MOODLE_INTERNAL') || die();

class ticket_manager {

    /** @var int Ticket time-to-live in seconds (5 minutes). */
    const TTL = 300;

    /** @var int Maximum failed OTP attempts before lockout. */
    const MAX_ATTEMPTS = 5;

    /**
     * Get an existing valid ticket or create a new one.
     *
     * @param int $userid The Moodle user ID.
     * @param int $blerifyid The blerify activity ID.
     * @return array {token, otp, expires_at, is_new}
     */
    public function get_or_create_ticket(int $userid, int $blerifyid): array {
        global $DB;

        $now = time();
        $sql = "SELECT * FROM {blerify_wallet_tickets}
                 WHERE userid = :userid AND blerifyid = :blerifyid
                   AND consumed = 0 AND expires_at > :now
                 ORDER BY timecreated DESC";
        $existing = $DB->get_record_sql($sql, [
            'userid' => $userid,
            'blerifyid' => $blerifyid,
            'now' => $now,
        ], IGNORE_MULTIPLE);

        if ($existing && $existing->attempts < self::MAX_ATTEMPTS) {
            return [
                'token' => $existing->token,
                'otp' => null,
                'expires_at' => (int)$existing->expires_at,
                'is_new' => false,
            ];
        }

        return $this->create_ticket($userid, $blerifyid);
    }

    /**
     * Create a new ticket for QR-based credential claiming.
     *
     * @param int $userid The Moodle user ID.
     * @param int $blerifyid The blerify activity ID.
     * @return array {token, otp, expires_at, is_new}
     */
    public function create_ticket(int $userid, int $blerifyid): array {
        global $DB;

        $DB->set_field_select(
            'blerify_wallet_tickets',
            'consumed',
            1,
            'userid = :userid AND blerifyid = :blerifyid AND consumed = 0',
            ['userid' => $userid, 'blerifyid' => $blerifyid]
        );

        $now = time();
        $token = bin2hex(random_bytes(32));
        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $record = new \stdClass();
        $record->userid = $userid;
        $record->blerifyid = $blerifyid;
        $record->token = $token;
        $record->hmac_secret = '';
        $record->otp = password_hash($otp, PASSWORD_DEFAULT);
        $record->consumed = 0;
        $record->attempts = 0;
        $record->expires_at = $now + self::TTL;
        $record->timecreated = $now;
        $DB->insert_record('blerify_wallet_tickets', $record);

        return [
            'token' => $token,
            'otp' => $otp,
            'expires_at' => $record->expires_at,
            'is_new' => true,
        ];
    }

    /**
     * Validate a claim from the wallet app.
     *
     * @param string $token The ticket token from the deeplink.
     * @param string $otp The OTP sent by the wallet.
     * @return array {success, error?, userid?, blerifyid?}
     */
    public function validate_claim(string $token, string $otp): array {
        global $DB;

        $ticket = $DB->get_record('blerify_wallet_tickets', ['token' => $token]);

        if (!$ticket) {
            return ['success' => false, 'error' => 'invalid_token'];
        }

        if ($ticket->consumed) {
            return ['success' => false, 'error' => 'token_already_used'];
        }

        if (time() > $ticket->expires_at) {
            return ['success' => false, 'error' => 'token_expired'];
        }

        if ($ticket->attempts >= self::MAX_ATTEMPTS) {
            return ['success' => false, 'error' => 'too_many_attempts'];
        }

        if (empty($otp) || !password_verify($otp, $ticket->otp)) {
            $DB->set_field('blerify_wallet_tickets', 'attempts', $ticket->attempts + 1, ['id' => $ticket->id]);
            return ['success' => false, 'error' => 'invalid_otp'];
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            $locked = $DB->get_record_sql(
                "SELECT * FROM {blerify_wallet_tickets} WHERE id = :id FOR UPDATE",
                ['id' => $ticket->id]
            );
            if (!$locked || $locked->consumed) {
                $transaction->allow_commit();
                return ['success' => false, 'error' => 'token_already_used'];
            }
            $DB->set_field('blerify_wallet_tickets', 'consumed', 1, ['id' => $ticket->id]);
            $transaction->allow_commit();
        } catch (\Exception $e) {
            try {
                $transaction->rollback($e);
            } catch (\Exception $ignored) {
            }
            return ['success' => false, 'error' => 'token_already_used'];
        }

        return [
            'success' => true,
            'userid' => (int)$ticket->userid,
            'blerifyid' => (int)$ticket->blerifyid,
        ];
    }

    /**
     * Store or update a user's wallet DID.
     *
     * @param int $userid
     * @param string $did
     */
    public function store_did(int $userid, string $did): void {
        global $DB;

        $now = time();
        $existing = $DB->get_record('blerify_wallet_dids', ['userid' => $userid]);

        if ($existing) {
            $existing->did = $did;
            $existing->timemodified = $now;
            $DB->update_record('blerify_wallet_dids', $existing);
        } else {
            $record = new \stdClass();
            $record->userid = $userid;
            $record->did = $did;
            $record->timecreated = $now;
            $record->timemodified = $now;
            $DB->insert_record('blerify_wallet_dids', $record);
        }
    }

    /**
     * Get the linked DID for a user.
     *
     * @param int $userid
     * @return string|null The DID or null if not linked.
     */
    public function get_did(int $userid): ?string {
        global $DB;

        $record = $DB->get_record('blerify_wallet_dids', ['userid' => $userid]);
        return $record ? $record->did : null;
    }

    /**
     * Get credential status for a user in an activity.
     *
     * @param int $userid
     * @param int $blerifyid The blerify activity ID.
     * @return string 'assembled', 'processing', 'error', or 'none'.
     */
    public function get_ticket_status(int $userid, int $blerifyid = 0): string {
        global $DB;

        if ($blerifyid > 0) {
            $credential = $DB->get_record('blerify_credentials', [
                'blerifyid' => $blerifyid,
                'userid' => $userid,
            ]);

            if (!$credential) {
                return 'none';
            }

            if ($credential->status === 'assembled') {
                return 'assembled';
            }

            if ($credential->status === 'error') {
                return 'error';
            }

            return 'processing';
        }

        $sql = "SELECT id, consumed, expires_at FROM {blerify_wallet_tickets}
                 WHERE userid = :userid
                 ORDER BY timecreated DESC";
        $ticket = $DB->get_record_sql($sql, ['userid' => $userid], IGNORE_MULTIPLE);

        if (!$ticket) {
            return 'none';
        }

        if ($ticket->consumed) {
            return 'linked';
        }

        if (time() <= $ticket->expires_at) {
            return 'pending';
        }

        return 'expired';
    }
}
