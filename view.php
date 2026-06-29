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
 * View page for mod_blerify.
 * Shows teacher view or student view.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/blerify/lib.php');


use mod_blerify\local\credentials;
use mod_blerify\wallet\ticket_manager;
use mod_blerify\wallet\qr_generator;

$id = required_param('id', PARAM_INT); 

$cm = get_coursemodule_from_id('blerify', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$blerify = $DB->get_record('blerify', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/blerify:view', $context);

$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/blerify/view.php', ['id' => $cm->id]);
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($blerify->name));
$PAGE->set_heading(format_string($course->fullname));

$credentialsmanager = new credentials();

$config = $DB->get_record('blerify_configs', ['id' => $blerify->configid]);

if (optional_param('action', '', PARAM_ALPHA) === 'resendotp') {
    require_sesskey();

    $ticketmanager = new ticket_manager();

    if ($ticketmanager->is_locked($USER->id, $blerify->id)) {
        redirect(
            new moodle_url('/mod/blerify/view.php', ['id' => $cm->id]),
            get_string('wallet_error_too_many_attempts', 'blerify'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $ticket = $ticketmanager->resend_otp($USER->id, $blerify->id);

    require_once($CFG->dirroot . '/mod/blerify/locallib.php');
    $emailsubject = get_string('otp_email_subject', 'blerify');
    $emailbody = get_string('otp_email_body', 'blerify', $ticket['otp']);
    $emailhtml = blerify_get_otp_email_html($ticket['otp']);
    $sent = email_to_user($USER, \core_user::get_noreply_user(), $emailsubject, $emailbody, $emailhtml);

    if (!$sent) {
        redirect(
            new moodle_url('/mod/blerify/view.php', ['id' => $cm->id]),
            get_string('otp_email_failed', 'blerify'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    redirect(
        new moodle_url('/mod/blerify/view.php', ['id' => $cm->id]),
        get_string('otp_resent', 'blerify'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if (has_capability('mod/blerify:manage', $context) && optional_param('action', '', PARAM_ALPHA) === 'retry') {
    require_sesskey();

    $retryuserid = required_param('retryuserid', PARAM_INT);
    $user = $DB->get_record('user', ['id' => $retryuserid], '*', MUST_EXIST);

    if (!is_enrolled($context, $retryuserid)) {
        throw new moodle_exception('usernotenrolled', 'blerify');
    }

    $DB->delete_records('blerify_credentials', [
        'blerifyid' => $blerify->id,
        'userid' => $retryuserid,
        'status' => 'error',
    ]);

    $now = time();
    $credrecord = new \stdClass();
    $credrecord->blerifyid = $blerify->id;
    $credrecord->userid = $retryuserid;
    $credrecord->status = 'authorized';
    $credrecord->timecreated = $now;
    $credrecord->timemodified = $now;
    $DB->insert_record('blerify_credentials', $credrecord);

    $message = get_string('retry_success', 'blerify');
    $type = \core\output\notification::NOTIFY_SUCCESS;

    redirect(new moodle_url('/mod/blerify/view.php', ['id' => $cm->id]), $message, null, $type);
}

if (has_capability('mod/blerify:manage', $context) && optional_param('action', '', PARAM_ALPHA) === 'issue') {
    require_sesskey();

    $selectedusers = optional_param_array('selectedusers', [], PARAM_INT);

    if (empty($selectedusers)) {
        redirect(
            new moodle_url('/mod/blerify/view.php', ['id' => $cm->id]),
            get_string('issue_no_selection', 'blerify'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }

    $success = 0;
    $fail = 0;

    foreach ($selectedusers as $userid) {
        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user || !is_enrolled($context, $userid)) {
            $fail++;
            continue;
        }

        $existing = $credentialsmanager->get_credential_for_user($blerify->id, $userid);
        if ($existing) {
            continue;
        }

        $now = time();
        $credrecord = new \stdClass();
        $credrecord->blerifyid = $blerify->id;
        $credrecord->userid = $userid;
        $credrecord->status = 'authorized';
        $credrecord->timecreated = $now;
        $credrecord->timemodified = $now;
        $credrecord->id = $DB->insert_record('blerify_credentials', $credrecord);

        \mod_blerify\event\credential_issued_manual::create([
            'context' => $context,
            'objectid' => $credrecord->id,
            'relateduserid' => $userid,
        ])->trigger();

        $success++;
    }

    if ($fail > 0 && $success > 0) {
        $message = get_string('issue_error_partial', 'blerify', (object)['success' => $success, 'fail' => $fail]);
        $type = \core\output\notification::NOTIFY_WARNING;
    } else if ($fail > 0) {
        $message = get_string('issue_error_partial', 'blerify', (object)['success' => $success, 'fail' => $fail]);
        $type = \core\output\notification::NOTIFY_ERROR;
    } else {
        $message = get_string('issue_success', 'blerify', $success);
        $type = \core\output\notification::NOTIFY_SUCCESS;
    }

    redirect(new moodle_url('/mod/blerify/view.php', ['id' => $cm->id]), $message, null, $type);
}

if (has_capability('mod/blerify:manage', $context)) {
    $allcredentials = $credentialsmanager->get_all_credentials($blerify->id);

    $templatedata = [
        'activityname' => format_string($blerify->name),
        'projectid' => $config ? $config->projectid : '-',
        'templateid' => $config ? $config->templateid : '-',
        'hascredentials' => !empty($allcredentials),
        'credentials' => [],
        'cmid' => $cm->id,
        'sesskey' => sesskey(),
    ];

    foreach ($allcredentials as $cred) {
        $templatedata['credentials'][] = [
            'fullname' => fullname($cred),
            'email' => $cred->email,
            'status' => get_string('status_' . $cred->status, 'blerify'),
            'is_error' => ($cred->status === 'error'),
            'userid' => $cred->userid,
            'credentialid' => $cred->credentialid ?: '-',
            'date' => ($cred->status === 'assembled') ? userdate($cred->timemodified) : '-',
            'cmid' => $cm->id,
            'sesskey' => sesskey(),
        ];
    }

    $enrolledusers = get_enrolled_users($context, 'mod/blerify:view', 0, 'u.*', 'u.lastname ASC');
    $issuedusersids = [];
    foreach ($allcredentials as $cred) {
        $issuedusersids[$cred->userid] = true;
    }

    $availableusers = [];
    foreach ($enrolledusers as $user) {
        if (isset($issuedusersids[$user->id])) {
            continue;
        }
        if (has_capability('mod/blerify:manage', $context, $user)) {
            continue;
        }
        $availableusers[] = [
            'userid' => $user->id,
            'fullname' => fullname($user),
            'email' => $user->email,
        ];
    }

    $templatedata['hasavailableusers'] = !empty($availableusers);
    $templatedata['availableusers'] = $availableusers;

    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('mod_blerify/view_teacher', $templatedata);
    echo $OUTPUT->footer();

} else {
    $credential = $credentialsmanager->get_credential_for_user($blerify->id, $USER->id);

    $ticketmanager = new ticket_manager();

    $smtpconfigured = !empty($CFG->smtphosts);

    $templatedata = [
        'activityname' => format_string($blerify->name),
        'has_credential' => !empty($credential),
        'is_assembled' => false,
        'is_processing' => false,
        'is_error' => false,
        'smtp_configured' => $smtpconfigured,
        'cmid' => $cm->id,
    ];

    if ($credential) {
        switch ($credential->status) {
            case 'assembled':
                $templatedata['is_assembled'] = true;
                $templatedata['credential_id'] = $credential->credentialid ?: '';
                $templatedata['sesskey'] = sesskey();
                break;
            case 'error':
            case 'authorized':
                break;
            default:
                $templatedata['is_processing'] = true;
                break;
        }
    }

    $reclaimrequested = optional_param('reclaim', 0, PARAM_BOOL);
    if ($reclaimrequested) {
        require_sesskey();
    }

    $needsclaim = (!$templatedata['is_assembled'] || $reclaimrequested) && !$templatedata['is_processing'];
    $templatedata['show_claim'] = $needsclaim && $smtpconfigured;
    $templatedata['show_smtp_warning'] = $needsclaim && !$smtpconfigured;
    $templatedata['claimed_done'] = $templatedata['is_assembled'] && !$templatedata['show_claim'];
    $templatedata['reclaim_url'] = (new moodle_url('/mod/blerify/view.php', ['id' => $cm->id]))->out(false);
    $templatedata['sesskey'] = sesskey();

    if ($templatedata['show_claim']) {
        $ticket = $ticketmanager->get_or_create_ticket($USER->id, $blerify->id);

        $claimurl = $CFG->wwwroot . '/mod/blerify/walletclaim.php/' . $ticket['token'];
        $deeplinkurl = $credentialsmanager->build_deeplink_v2($claimurl);

        $qrpng = qr_generator::generate($deeplinkurl);
        $templatedata['qr_data_url'] = 'data:image/png;base64,' . $qrpng;
        $templatedata['qr_expires_at'] = $ticket['expires_at'];

        if ($ticket['is_new']) {
            $templatedata['otp'] = $ticket['otp'];

            require_once($CFG->dirroot . '/mod/blerify/locallib.php');
            $emailsubject = get_string('otp_email_subject', 'blerify');
            $emailbody = get_string('otp_email_body', 'blerify', $ticket['otp']);
            $emailhtml = blerify_get_otp_email_html($ticket['otp']);
            $sent = email_to_user($USER, \core_user::get_noreply_user(), $emailsubject, $emailbody, $emailhtml);
            $templatedata['otp_email_failed'] = !$sent;
        } else {
            $templatedata['otp_previously_sent'] = true;
        }

        $templatedata['resendotp_url'] = (new moodle_url('/mod/blerify/view.php', ['id' => $cm->id]))->out(false);
        $templatedata['sesskey'] = sesskey();

        $statusurl = new moodle_url('/mod/blerify/walletstatus.php');
        $refreshurl = new moodle_url('/mod/blerify/view.php', ['id' => $cm->id]);

        $PAGE->requires->js_call_amd('mod_blerify/wallet_qr', 'init', [[
            'expiresAt' => $ticket['expires_at'],
            'statusUrl' => $statusurl->out(false),
            'sesskey' => sesskey(),
            'cmid' => $cm->id,
            'refreshUrl' => $refreshurl->out(false),
            'since' => $credential ? (int)$credential->timemodified : 0,
        ]]);
    }

    if ($templatedata['is_processing']) {
        $statusurl = new moodle_url('/mod/blerify/walletstatus.php');
        $refreshurl = new moodle_url('/mod/blerify/view.php', ['id' => $cm->id]);

        $PAGE->requires->js_call_amd('mod_blerify/wallet_qr', 'init', [[
            'expiresAt' => time() + 300,
            'statusUrl' => $statusurl->out(false),
            'sesskey' => sesskey(),
            'cmid' => $cm->id,
            'refreshUrl' => $refreshurl->out(false),
        ]]);
    }

    $templatedata['appstore_url'] = 'https://apps.apple.com/app/blerify/id6740080426';
    $templatedata['playstore_url'] = 'https://play.google.com/store/apps/details?id=com.nicoinc.lacchainid';
    $templatedata['appstore_img'] = (new moodle_url('/mod/blerify/pix/appstore.jpg'))->out(false);
    $templatedata['playstore_img'] = (new moodle_url('/mod/blerify/pix/googleplay.jpg'))->out(false);

    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('mod_blerify/view_student', $templatedata);
    echo $OUTPUT->footer();
}
