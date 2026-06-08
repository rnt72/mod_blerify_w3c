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
use mod_blerify\wallet\ticket_manager;

/**
 * Build the HTML body for the OTP verification email.
 *
 * @param string $otp The one-time verification code.
 * @return string The full HTML email body.
 */
function blerify_get_otp_email_html($otp) {
    global $CFG;

    $headerimg = $CFG->wwwroot . '/mod/blerify/pix/email_header.png';
    $logoimg = $CFG->wwwroot . '/mod/blerify/pix/blerify.png';

    return '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blerify - Verification Code</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:\'Open Sans\',Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
 style="background-color:#f5f7fa;">
<tr>
<td align="center" style="padding:20px 10px;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
 style="max-width:600px;">

<!-- Header banner -->
<tr>
<td align="center" style="padding:0;">
<img src="' . $headerimg . '" alt="Blerify"
 style="display:block;width:100%;max-width:600px;height:auto;border-radius:25px 25px 0 0;" />
</td>
</tr>

<!-- Main card -->
<tr>
<td style="background-color:#ffffff;padding:0 30px;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
 style="border:1px solid #e5e5e5;border-radius:25px;overflow:hidden;">

<!-- Title -->
<tr>
<td style="padding:35px 30px 10px;text-align:left;">
<p style="margin:0;font-size:19px;font-weight:700;line-height:1.4;color:#000000;">
' . get_string('otp_email_html_title', 'blerify') . '
</p>
</td>
</tr>

<!-- Body text -->
<tr>
<td style="padding:10px 30px 5px;text-align:left;">
<p style="margin:0;font-size:17px;line-height:1.4;color:#000000;">
' . get_string('otp_email_html_greeting', 'blerify') . '
</p>
</td>
</tr>

<!-- OTP code box -->
<tr>
<td style="padding:25px 30px;" align="center">
<table role="presentation" cellpadding="0" cellspacing="0" border="0">
<tr>
<td style="background-color:#31c665;border-radius:16px;padding:18px 50px;text-align:center;">
<span style="font-size:32px;font-weight:700;letter-spacing:8px;color:#ffffff;font-family:\'Courier New\',monospace;">
' . s($otp) . '
</span>
</td>
</tr>
</table>
</td>
</tr>

<!-- Expiry notice -->
<tr>
<td style="padding:0 30px 30px;text-align:center;">
<p style="margin:0;font-size:15px;line-height:1.4;color:#606789;">
' . get_string('otp_email_html_expiry', 'blerify') . '
</p>
</td>
</tr>

<!-- Welcome message -->
<tr>
<td style="padding:10px 30px 30px;text-align:center;">
<p style="margin:0;font-size:17px;line-height:1.4;color:#000000;">
' . get_string('otp_email_html_welcome', 'blerify') . '
</p>
</td>
</tr>

<!-- Footer inside card -->
<tr>
<td style="background-color:#f5f7fa;border-radius:0 0 25px 25px;padding:25px 30px;text-align:center;">
<p style="margin:0;font-size:14px;line-height:1.4;color:#606789;">
' . get_string('otp_email_html_footer', 'blerify') . '
</p>
</td>
</tr>

</table>
</td>
</tr>

<!-- Blerify.com link -->
<tr>
<td style="padding:20px 0;text-align:center;">
<a href="https://blerify.com/" style="text-decoration:none;color:#2e95d3;font-size:17px;">Blerify.com</a>
</td>
</tr>

</table>

</td>
</tr>
</table>
</body>
</html>';
}

/**
 * Course completion handler.
 *
 * @param \core\event\course_completed $event
 */
function blerify_course_completed_handler($event) {
    global $DB;

    $user = $DB->get_record('user', ['id' => $event->relateduserid]);
    if (!$user) {
        return;
    }

    $records = $DB->get_records('blerify', ['course' => $event->courseid]);
    if (!$records) {
        return;
    }

    $credentialsmanager = new credentials();
    $ticketmanager = new ticket_manager();
    $walletdid = $ticketmanager->get_did($user->id);

    foreach ($records as $record) {
        if (empty($record->completionissue)) {
            continue;
        }

        $existing = $DB->get_record('blerify_credentials', [
            'blerifyid' => $record->id,
            'userid' => $user->id,
        ]);
        if ($existing) {
            continue;
        }

        $config = $DB->get_record('blerify_configs', ['id' => $record->configid]);
        if (!$config) {
            debugging('Blerify: No config found for activity ' . $record->id, DEBUG_NORMAL);
            continue;
        }

        if (!empty($walletdid)) {
            try {
                $credresult = $credentialsmanager->issue_credential($record, $config, $user, $walletdid);

                $cm = get_coursemodule_from_instance('blerify', $record->id, $record->course);
                if ($cm) {
                    $certificateevent = \mod_blerify\event\certificate_created::create([
                        'objectid' => $credresult->id,
                        'context' => context_module::instance($cm->id),
                        'relateduserid' => $user->id,
                    ]);
                    $certificateevent->trigger();
                }
            } catch (\Exception $e) {
                debugging('Blerify: Failed to issue credential for user ' . $user->id .
                    ' in activity ' . $record->id . ': ' . $e->getMessage(), DEBUG_NORMAL);
            }
        } else {
            $subject = get_string('completion_notification_subject', 'blerify');
            $body = get_string('completion_notification_body', 'blerify');
            email_to_user($user, \core_user::get_noreply_user(), $subject, $body);
        }
    }
}
