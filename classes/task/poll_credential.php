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
 * Ad-hoc task that follows an asynchronous issuance until the credential is ready.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_blerify\task;

defined('MOODLE_INTERNAL') || die();

use mod_blerify\local\credentials;

class poll_credential extends \core\task\adhoc_task {

    /** @var int How often the task retries while the credential is still being assembled. */
    const RETRY_DELAY = 60;

    /** @var int Give up after this many attempts, so a stuck credential is not polled forever. */
    const MAX_ATTEMPTS = 15;

    /**
     * Queue a polling run for a credential record.
     *
     * @param int $credentialrecordid Id in blerify_credentials.
     * @param int $attempt Attempt number, used to stop retrying eventually.
     * @param int $delay Seconds to wait before the first run.
     */
    public static function queue($credentialrecordid, $attempt = 1, $delay = 0) {
        $task = new self();
        $task->set_custom_data([
            'credentialrecordid' => $credentialrecordid,
            'attempt' => $attempt,
        ]);
        if ($delay > 0) {
            $task->set_next_run_time(time() + $delay);
        }

        \core\task\manager::queue_adhoc_task($task);
    }

    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        $credrecord = $DB->get_record('blerify_credentials', ['id' => $data->credentialrecordid]);

        if (!$credrecord) {
            return;
        }

        if (in_array($credrecord->status, [credentials::STATUS_ISSUED, credentials::STATUS_CLAIMED], true)) {
            return;
        }

        $manager = new credentials();

        try {
            $credrecord = $manager->refresh($credrecord);
        } catch (\Exception $e) {
            mtrace('Blerify: polling failed for credential ' . $credrecord->id . ': ' . $e->getMessage());
            $this->requeue($data);
            return;
        }

        if ($credrecord->status === credentials::STATUS_ISSUING) {
            $this->requeue($data);
            return;
        }

        mtrace('Blerify: credential ' . $credrecord->id . ' reached status ' . $credrecord->status);
    }

    /**
     * Schedule another attempt, or record the timeout once the budget is spent.
     *
     * @param object $data The current custom data.
     */
    private function requeue($data) {
        global $DB;

        $attempt = (int)$data->attempt + 1;

        if ($attempt > self::MAX_ATTEMPTS) {
            $DB->set_field('blerify_credentials', 'status', credentials::STATUS_ERROR,
                ['id' => $data->credentialrecordid]);
            $DB->set_field('blerify_credentials', 'errordetail', 'polling_timeout',
                ['id' => $data->credentialrecordid]);
            mtrace('Blerify: gave up polling credential ' . $data->credentialrecordid);
            return;
        }

        self::queue($data->credentialrecordid, $attempt, self::RETRY_DELAY);
    }
}
