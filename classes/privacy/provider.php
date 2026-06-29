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
 * Privacy provider for mod_blerify (GDPR compliance).
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_blerify\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe the stored user data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('blerify_credentials', [
            'userid' => 'privacy:metadata:blerify_credentials:userid',
            'credentialid' => 'privacy:metadata:blerify_credentials:credentialid',
            'status' => 'privacy:metadata:blerify_credentials:status',
            'wallet_did' => 'privacy:metadata:blerify_credentials:wallet_did',
        ], 'privacy:metadata:blerify_credentials');

        $collection->add_database_table('blerify_wallet_dids', [
            'userid' => 'privacy:metadata:blerify_wallet_dids:userid',
            'did' => 'privacy:metadata:blerify_wallet_dids:did',
        ], 'privacy:metadata:blerify_wallet_dids');

        $collection->add_database_table('blerify_wallet_tickets', [
            'userid' => 'privacy:metadata:blerify_wallet_tickets:userid',
        ], 'privacy:metadata:blerify_wallet_tickets');

        $collection->add_database_table('blerify_wallet_lockouts', [
            'userid' => 'privacy:metadata:blerify_wallet_lockouts:userid',
            'failcount' => 'privacy:metadata:blerify_wallet_lockouts:failcount',
            'lockeduntil' => 'privacy:metadata:blerify_wallet_lockouts:lockeduntil',
        ], 'privacy:metadata:blerify_wallet_lockouts');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'blerify'
                  JOIN {blerify} b ON b.id = cm.instance
                  JOIN {blerify_credentials} bc ON bc.blerifyid = b.id
                 WHERE bc.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'blerify'
                  JOIN {blerify} b ON b.id = cm.instance
                  JOIN {blerify_wallet_tickets} bt ON bt.blerifyid = b.id
                 WHERE bt.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'blerify'
                  JOIN {blerify} b ON b.id = cm.instance
                  JOIN {blerify_wallet_lockouts} bl ON bl.blerifyid = b.id
                 WHERE bl.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get the list of users within a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT bc.userid
                  FROM {blerify_credentials} bc
                  JOIN {blerify} b ON b.id = bc.blerifyid
                  JOIN {course_modules} cm ON cm.instance = b.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'blerify'
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, ['cmid' => $context->instanceid]);

        $sql = "SELECT bt.userid
                  FROM {blerify_wallet_tickets} bt
                  JOIN {blerify} b ON b.id = bt.blerifyid
                  JOIN {course_modules} cm ON cm.instance = b.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'blerify'
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, ['cmid' => $context->instanceid]);

        $sql = "SELECT bl.userid
                  FROM {blerify_wallet_lockouts} bl
                  JOIN {blerify} b ON b.id = bl.blerifyid
                  JOIN {course_modules} cm ON cm.instance = b.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'blerify'
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, ['cmid' => $context->instanceid]);
    }

    /**
     * Export user data.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('blerify', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $credentials = $DB->get_records('blerify_credentials', [
                'blerifyid' => $cm->instance,
                'userid' => $userid,
            ]);

            foreach ($credentials as $credential) {
                $data = (object) [
                    'credentialid' => $credential->credentialid,
                    'status' => $credential->status,
                    'wallet_did' => $credential->wallet_did,
                    'timecreated' => \core_privacy\local\request\transform::datetime($credential->timecreated),
                ];
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'blerify'), 'credentials'],
                    $data
                );
            }

            $tickets = $DB->get_records('blerify_wallet_tickets', [
                'blerifyid' => $cm->instance,
                'userid' => $userid,
            ]);

            foreach ($tickets as $ticket) {
                $data = (object) [
                    'consumed' => $ticket->consumed,
                    'expires_at' => \core_privacy\local\request\transform::datetime($ticket->expires_at),
                    'timecreated' => \core_privacy\local\request\transform::datetime($ticket->timecreated),
                ];
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'blerify'), 'wallet_tickets'],
                    $data
                );
            }

            $lockouts = $DB->get_records('blerify_wallet_lockouts', [
                'blerifyid' => $cm->instance,
                'userid' => $userid,
            ]);

            foreach ($lockouts as $lockout) {
                $data = (object) [
                    'failcount' => $lockout->failcount,
                    'lockeduntil' => \core_privacy\local\request\transform::datetime($lockout->lockeduntil),
                    'timemodified' => \core_privacy\local\request\transform::datetime($lockout->timemodified),
                ];
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'blerify'), 'wallet_lockouts'],
                    $data
                );
            }
        }

        $walletdid = $DB->get_record('blerify_wallet_dids', ['userid' => $userid]);
        if ($walletdid) {
            $contexts = $contextlist->get_contexts();
            $firstcontext = reset($contexts);
            if ($firstcontext) {
                $data = (object) [
                    'did' => $walletdid->did,
                    'timecreated' => \core_privacy\local\request\transform::datetime($walletdid->timecreated),
                ];
                writer::with_context($firstcontext)->export_data(
                    [get_string('pluginname', 'blerify'), 'wallet_did'],
                    $data
                );
            }
        }
    }

    /**
     * Delete all data for all users in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('blerify', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records('blerify_wallet_tickets', ['blerifyid' => $cm->instance]);
        $DB->delete_records('blerify_wallet_lockouts', ['blerifyid' => $cm->instance]);
        $DB->delete_records('blerify_credentials', ['blerifyid' => $cm->instance]);
    }

    /**
     * Delete data for a specific user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('blerify', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $DB->delete_records('blerify_wallet_tickets', [
                'blerifyid' => $cm->instance,
                'userid' => $userid,
            ]);
            $DB->delete_records('blerify_wallet_lockouts', [
                'blerifyid' => $cm->instance,
                'userid' => $userid,
            ]);
            $DB->delete_records('blerify_credentials', [
                'blerifyid' => $cm->instance,
                'userid' => $userid,
            ]);
        }

        $DB->delete_records('blerify_wallet_dids', ['userid' => $userid]);
    }

    /**
     * Delete data for users in a context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('blerify', $context->instanceid);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $inparams['blerifyid'] = $cm->instance;

        $DB->delete_records_select(
            'blerify_wallet_tickets',
            "blerifyid = :blerifyid AND userid $insql",
            $inparams
        );

        $DB->delete_records_select(
            'blerify_wallet_lockouts',
            "blerifyid = :blerifyid AND userid $insql",
            $inparams
        );

        $DB->delete_records_select(
            'blerify_credentials',
            "blerifyid = :blerifyid AND userid $insql",
            $inparams
        );

        foreach ($userids as $uid) {
            $DB->delete_records('blerify_wallet_dids', ['userid' => $uid]);
        }
    }
}
