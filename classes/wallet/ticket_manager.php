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

    /** @var int Failed attempts (per user/activity) before backoff begins. */
    const LOCKOUT_THRESHOLD = 5;

    /** @var int Base lockout duration in seconds. */
    const LOCKOUT_BASE = 60;

    /** @var int Maximum lockout duration in seconds. */
    const LOCKOUT_MAX = 3600;

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
     * Issue a fresh OTP for the current valid ticket, keeping the same token.
     *
     * Preserves the QR/deeplink and expiry window; only the OTP changes. Falls
     * back to creating a ticket when none is currently valid.
     *
     * @param int $userid The Moodle user ID.
     * @param int $blerifyid The blerify activity ID.
     * @return array {token, otp, expires_at, is_new}
     */
    public function resend_otp(int $userid, int $blerifyid): array {
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

        if (!$existing) {
            return $this->create_ticket($userid, $blerifyid);
        }

        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $existing->otp = password_hash($otp, PASSWORD_DEFAULT);
        $DB->update_record('blerify_wallet_tickets', $existing);

        return [
            'token' => $existing->token,
            'otp' => $otp,
            'expires_at' => (int)$existing->expires_at,
            'is_new' => false,
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

        if ($this->is_locked((int)$ticket->userid, (int)$ticket->blerifyid)) {
            return [
                'success' => false,
                'error' => 'too_many_attempts',
                'userid' => (int)$ticket->userid,
                'blerifyid' => (int)$ticket->blerifyid,
                'locked' => true,
            ];
        }

        if (empty($otp) || !password_verify($otp, $ticket->otp)) {
            $DB->set_field('blerify_wallet_tickets', 'attempts', $ticket->attempts + 1, ['id' => $ticket->id]);
            $locked = $this->register_failure((int)$ticket->userid, (int)$ticket->blerifyid);
            return [
                'success' => false,
                'error' => 'invalid_otp',
                'userid' => (int)$ticket->userid,
                'blerifyid' => (int)$ticket->blerifyid,
                'locked' => $locked,
            ];
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

        $this->clear_lockout((int)$ticket->userid, (int)$ticket->blerifyid);

        return [
            'success' => true,
            'userid' => (int)$ticket->userid,
            'blerifyid' => (int)$ticket->blerifyid,
        ];
    }

    /**
     * Check whether a user is currently locked out for an activity.
     *
     * @param int $userid
     * @param int $blerifyid
     * @return bool
     */
    public function is_locked(int $userid, int $blerifyid): bool {
        global $DB;

        $record = $DB->get_record('blerify_wallet_lockouts', [
            'userid' => $userid,
            'blerifyid' => $blerifyid,
        ]);

        return $record && (int)$record->lockeduntil > time();
    }

    /**
     * Register a failed attempt and apply backoff once the threshold is reached.
     *
     * @param int $userid
     * @param int $blerifyid
     * @return bool True when the user is now locked out.
     */
    public function register_failure(int $userid, int $blerifyid): bool {
        global $DB;

        $now = time();
        $record = $DB->get_record('blerify_wallet_lockouts', [
            'userid' => $userid,
            'blerifyid' => $blerifyid,
        ]);

        if (!$record) {
            $record = new \stdClass();
            $record->userid = $userid;
            $record->blerifyid = $blerifyid;
            $record->failcount = 0;
            $record->lockeduntil = 0;
            $record->lastfailtime = 0;
            $record->timecreated = $now;
            $record->timemodified = $now;
            $record->id = $DB->insert_record('blerify_wallet_lockouts', $record);
        }

        $record->failcount++;
        $record->lastfailtime = $now;
        $record->timemodified = $now;

        $locked = false;
        if ($record->failcount >= self::LOCKOUT_THRESHOLD) {
            $exponent = $record->failcount - self::LOCKOUT_THRESHOLD;
            $duration = min(self::LOCKOUT_BASE * (2 ** $exponent), self::LOCKOUT_MAX);
            $record->lockeduntil = $now + $duration;
            $locked = true;
        }

        $DB->update_record('blerify_wallet_lockouts', $record);

        return $locked;
    }

    /**
     * Clear the lockout state for a user/activity after a successful claim.
     *
     * @param int $userid
     * @param int $blerifyid
     */
    public function clear_lockout(int $userid, int $blerifyid): void {
        global $DB;

        $DB->delete_records('blerify_wallet_lockouts', [
            'userid' => $userid,
            'blerifyid' => $blerifyid,
        ]);
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
