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
 * Local library functions for mod_blerify
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_blerify\local\credentials;

/**
 * The Blerify project a course issues under.
 *
 * Resolved in order of precedence: a per-course override, the project declared
 * inside the service account file, then the site-wide setting. The API needs a
 * project in every path, but it is the same one for the whole organization in
 * the normal case, so it is configured once next to the service account.
 *
 * @param int $courseid Course to resolve for, 0 to skip the per-course override.
 * @return string The project UUID, or '' when none is configured.
 */
function blerify_get_project_id($courseid = 0) {
    global $DB;

    if ($courseid) {
        $override = $DB->get_field('blerify_configs', 'projectid', ['courseid' => $courseid]);
        if (!empty($override)) {
            return $override;
        }
    }

    $sa = \mod_blerify\local\service_account::get_decoded();
    if (!empty($sa['project_id'])) {
        return $sa['project_id'];
    }

    return trim((string) get_config('mod_blerify', 'project_id'));
}

/**
 * The credential templates available in a project.
 *
 * @param string $projectid
 * @return array As returned by apirest::get_templates().
 * @throws \Exception When the list cannot be retrieved.
 */
function blerify_get_templates($projectid) {
    return (new \mod_blerify\apirest\apirest(new \mod_blerify\client\client()))
        ->get_templates($projectid);
}

/**
 * The learner's course grade as a percentage of the maximum.
 *
 * @param int $courseid
 * @param int $userid
 * @return float|null The percentage, or null when the learner has no course grade yet.
 */
function blerify_get_course_grade_percentage($courseid, $userid) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $courseitem = grade_item::fetch_course_item($courseid);
    if (!$courseitem) {
        return null;
    }

    $grade = $courseitem->get_grade($userid, false);
    if (!$grade || $grade->finalgrade === null || $grade->finalgrade === false) {
        return null;
    }

    $grademax = (float)$courseitem->grademax;
    $grademin = (float)$courseitem->grademin;
    $range = $grademax - $grademin;

    if ($range <= 0) {
        return null;
    }

    return (((float)$grade->finalgrade - $grademin) / $range) * 100;
}

/**
 * Queue issuance in a course for every activity whose grade threshold the
 * learner now meets.
 *
 * The API calls happen in an ad-hoc task, so a bulk regrade queues work instead
 * of holding the grader while Blerify responds.
 *
 * @param int $courseid
 * @param int $userid
 */
function blerify_issue_for_qualified_user($courseid, $userid) {
    global $DB;

    $records = $DB->get_records('blerify', ['course' => $courseid]);
    if (!$records) {
        return;
    }

    $user = $DB->get_record('user', ['id' => $userid], 'id, deleted');
    if (!$user || !empty($user->deleted)) {
        return;
    }

    $manager = new credentials();
    $percentage = null;

    foreach ($records as $record) {
        if (empty($record->completionissue) || empty($record->templateid)) {
            continue;
        }

        // A record already exists for every state worth keeping, including a
        // failed one: retrying a failure is a deliberate teacher action, so a
        // failing API is not retried on every grade change.
        if ($manager->get_credential_for_user($record->id, $userid)) {
            continue;
        }

        if ($percentage === null) {
            $percentage = blerify_get_course_grade_percentage($courseid, $userid);
            if ($percentage === null) {
                return;
            }
        }

        if ($percentage < (float)$record->passgrade) {
            continue;
        }

        \mod_blerify\task\issue_credential::queue($record->id, $userid);
    }
}

/**
 * Course completion handler.
 *
 * @param \core\event\course_completed $event
 */
function blerify_course_completed_handler($event) {
    blerify_issue_for_qualified_user($event->courseid, $event->relateduserid);
}
