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
 * English strings for mod_blerify
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Module metadata.
$string['modulename'] = 'Blerify Certificate';
$string['modulenameplural'] = 'Blerify Certificates';
$string['modulename_help'] = 'The Blerify Certificate activity module allows you to issue W3C Verifiable Credentials to students via the Blerify platform.';
$string['pluginadministration'] = 'Blerify Certificate administration';
$string['pluginname'] = 'Blerify Certificate';

// Admin settings.
$string['setting_service_account_json'] = 'Service Account JSON';
$string['setting_service_account_json_desc'] = 'Upload your Blerify service account JSON file (e.g. service-account-SA2-sa-XXXXXXX.json). This file contains all the credentials needed: client_id, organization_id, private_key, token_uri, and iam_audience.';
$string['setting_service_account_upload_help'] = 'Select a .json service account file to import.';
$string['setting_service_account_current'] = 'Current service account (read-only):';
$string['setting_service_account_configured'] = 'Service account imported successfully';
$string['setting_service_account_invalid_json'] = 'The uploaded file does not contain valid JSON.';

// Activity form.
$string['blerifysettings'] = 'Blerify Settings';
$string['projectid'] = 'Project ID';
$string['projectid_help'] = 'UUID of the Blerify project for this certificate';
$string['templateid'] = 'Template ID';
$string['templateid_help'] = 'UUID of the Blerify credential template';
$string['projectid_placeholder'] = 'e.g. 60f1a2b3-c4d5-6e7f-8a9b-0c1d2e3f4a5b';
$string['templateid_placeholder'] = 'e.g. 50a1b2c3-d4e5-6f7a-8b9c-0d1e2f3a4b5c';
$string['completionissue'] = 'Issue on course completion';
$string['completionissue_help'] = 'Automatically issue a certificate when the student completes the course';

// Validation errors.
$string['error_projectid_required'] = 'Project ID is required';
$string['error_templateid_required'] = 'Template ID is required';

// View page.
$string['viewheader'] = 'Certificates for: {$a}';
$string['nocertificates'] = 'There are no Blerify certificate activities in this course.';
$string['indexheader'] = 'Blerify Certificates in: {$a}';

// Student view.
$string['claim_certificate'] = 'Claim Certificate';
$string['certificate_processing'] = 'Your certificate is being processed. Please check back later.';
$string['certificate_error'] = 'There was an error issuing your certificate. Please contact your instructor.';
$string['certificate_pending'] = 'Complete the course to receive your digital certificate.';

// Teacher view.
$string['col_student'] = 'Student';
$string['col_email'] = 'Email';
$string['col_status'] = 'Status';
$string['col_credentialid'] = 'Credential ID';
$string['col_date'] = 'Date Issued';
$string['no_credentials_issued'] = 'No credentials have been issued yet.';

// Statuses.
$string['status_pending'] = 'Pending';
$string['status_created'] = 'Created';
$string['status_signed'] = 'Signed';
$string['status_assembled'] = 'Assembled';
$string['status_authorized'] = 'Authorized';
$string['status_error'] = 'Error';

// Events.
$string['eventcertificatecreated'] = 'Blerify certificate created';
$string['eventotpfailed'] = 'Wallet OTP attempt failed';
$string['eventotplockout'] = 'Wallet OTP lockout';
$string['eventclaimratelimited'] = 'Wallet claim rate limited';
$string['eventclaimsucceeded'] = 'Wallet claim succeeded';
$string['eventdidlinked'] = 'Wallet DID linked';
$string['eventcredentialissuedmanual'] = 'Credential issued manually';
$string['eventcredentialreissued'] = 'Credential reissued';
$string['eventcredentialissuancefailed'] = 'Credential issuance failed';

// Privacy.
$string['privacy:metadata:blerify_credentials'] = 'Information about Blerify digital credentials issued to users';
$string['privacy:metadata:blerify_credentials:userid'] = 'The user ID of the credential recipient';
$string['privacy:metadata:blerify_credentials:credentialid'] = 'The external credential identifier in Blerify';
$string['privacy:metadata:blerify_credentials:status'] = 'The current status of the credential';
$string['privacy:metadata:blerify_credentials:wallet_did'] = 'The DID of the user wallet';
$string['privacy:metadata:blerify_wallet_dids'] = 'Wallet DIDs linked to user accounts';
$string['privacy:metadata:blerify_wallet_dids:userid'] = 'The user ID of the wallet owner';
$string['privacy:metadata:blerify_wallet_dids:did'] = 'The decentralized identifier of the user wallet';
$string['privacy:metadata:blerify_wallet_tickets'] = 'Temporary tickets used for QR-based credential claiming';
$string['privacy:metadata:blerify_wallet_tickets:userid'] = 'The user ID of the ticket holder';
$string['privacy:metadata:blerify_wallet_lockouts'] = 'OTP lockout state to throttle wallet claim attempts';
$string['privacy:metadata:blerify_wallet_lockouts:userid'] = 'The user ID subject to the lockout';
$string['privacy:metadata:blerify_wallet_lockouts:failcount'] = 'The number of consecutive failed OTP attempts';
$string['privacy:metadata:blerify_wallet_lockouts:lockeduntil'] = 'The time until which claiming is blocked';

// Errors.
$string['error_not_configured'] = 'Blerify plugin is not properly configured. Please contact the administrator.';
$string['error_api_call'] = 'Error communicating with Blerify API: {$a}';
$string['error_no_wallet_did'] = 'Cannot issue credential: the student has not linked a wallet. They must scan the QR code with the Blerify Wallet app first.';

// Admin config management.
$string['manage_configs'] = 'Manage Blerify Configs';
$string['add_config'] = 'Add certificate configuration';
$string['edit_config'] = 'Edit certificate configuration';
$string['config_name'] = 'Configuration name';
$string['config_name_placeholder'] = 'e.g. Diploma Course 2024';
$string['config_saved'] = 'Certificate configuration saved successfully.';
$string['config_deleted'] = 'Certificate configuration deleted.';
$string['config_delete_confirm'] = 'Are you sure you want to delete this configuration?';
$string['existing_configs'] = 'Existing configurations';
$string['no_configs'] = 'No certificate configurations have been created yet.';
$string['error_no_config_for_course'] = 'No Blerify certificate configuration exists for this course. Please ask the administrator to configure one.';
$string['error_course_not_found'] = 'The selected course was not found.';
$string['error_course_already_configured'] = 'This course already has a Blerify certificate configuration.';
$string['error_invalid_uuid'] = 'Project ID and Template ID must be valid UUIDs.';
$string['unknowncourse'] = 'Unknown course';

// Manual issuance.
$string['issue_credentials'] = 'Enable Credentials';
$string['issue_credentials_header'] = 'Enrolled Participants';
$string['issue_credentials_help'] = 'Select the participants you want to enable a digital credential for so they can claim it. Only participants without an existing credential are shown.';
$string['issue_selected'] = 'Enable for Selected';
$string['no_participants_available'] = 'All enrolled participants already have a credential, or there are no participants enrolled in this course.';
$string['select_all'] = 'Select all';
$string['deselect_all'] = 'Deselect all';
$string['issue_success'] = '{$a} credential(s) enabled. The student can now claim it.';
$string['issue_error_partial'] = '{$a->success} credential(s) enabled. {$a->fail} failed.';
$string['issue_no_selection'] = 'No participants were selected.';
$string['col_participant'] = 'Participant';
$string['col_actions'] = 'Actions';
$string['already_issued'] = 'Already enabled';
$string['retry_issue'] = 'Retry';
$string['retry_success'] = 'Credential re-enabled successfully.';
$string['retry_failed'] = 'Retry failed. Check the logs for details.';

// Wallet connect.
$string['wallet_connect_title'] = 'Connect your Blerify Wallet';
$string['wallet_connect_desc'] = 'Scan the QR code with your phone to open the Blerify Wallet app and link your digital identity. If you don\'t have the app yet, download it from the links below.';
$string['wallet_connect_desc_v2'] = 'Scan the QR code with your phone to open the Blerify Wallet app. Your credential will be issued automatically. If you don\'t have the app yet, download it from the links below.';
$string['wallet_download_title'] = 'Download your wallet';
$string['wallet_download_prompt'] = 'Don\'t have the wallet yet? Download it here:';
$string['wallet_download_ios'] = 'Download on App Store';
$string['wallet_download_android'] = 'Get it on Google Play';
$string['wallet_qr_expires_in'] = 'QR code expires in: {$a}';
$string['wallet_qr_refresh'] = 'Generate new QR code';
$string['wallet_qr_expired'] = 'The QR code has expired.';
$string['wallet_linked_title'] = 'Wallet Connected';
$string['wallet_linked_desc'] = 'Your Blerify Wallet has been successfully linked to your account.';
$string['wallet_error_invalid_token'] = 'Invalid or unrecognized QR token.';
$string['wallet_error_token_used'] = 'This QR code has already been used.';
$string['wallet_error_token_expired'] = 'This QR code has expired.';
$string['wallet_error_too_many_attempts'] = 'Too many failed attempts. Please generate a new QR code.';

// OTP and claim.
$string['wallet_otp_label'] = 'Verification code';
$string['wallet_did_current'] = 'Your current DID: {$a}';
$string['otp_also_sent_email'] = 'Also sent to your email';
$string['otp_resend_btn'] = 'Resend verification code';
$string['smtp_not_configured'] = 'SMTP email not configured, please contact the administrator';
$string['smtp_not_configured_title'] = 'Email service not configured';
$string['smtp_not_configured_desc'] = 'The SMTP email service must be enabled to issue credentials, as the verification code is sent by email. Please contact the site administrator.';
$string['otp_resent'] = 'A new verification code has been sent to your email.';
$string['otp_email_failed'] = 'We could not send the verification code by email. Please try again.';
$string['otp_email_subject'] = 'Blerify - Verification code';
$string['otp_email_body'] = 'Your verification code to claim your Blerify credential is: {$a}. This code expires in 5 minutes.';
$string['otp_email_html_title'] = 'Verification Code!';
$string['otp_email_html_greeting'] = '<b>Hello!</b><br>A verification code has been generated to claim your digital credential in your <b>Blerify</b> wallet. Enter the following code on the platform:';
$string['otp_email_html_expiry'] = 'This code expires in 5 minutes.';
$string['otp_email_html_welcome'] = 'Welcome to the future, where you own your data!';
$string['otp_email_html_footer'] = 'You receive this email because you have a Blerify&trade; account. If you are unsure why you received this email, contact us at <a href="mailto:support@blerify.com" style="text-decoration:none;font-weight:600;color:#2e95d3;">support@blerify.com</a>.';
$string['claim_certificate_btn'] = 'Claim your Certificate';
$string['reclaim_certificate_btn'] = 'Reclaim certificate';
$string['claim_requires_wallet'] = 'You need to link your wallet first';
$string['claim_success'] = 'Credential issued successfully';
$string['reclaim_btn'] = 'Re-claim credential';
$string['credential_assembled_desc'] = 'Your credential has been successfully issued to your wallet.';
$string['claim_error_no_did'] = 'Cannot claim: wallet not linked';
$string['claim_error_already'] = 'You already have a credential for this activity';
$string['wallet_error_invalid_otp'] = 'Invalid verification code.';

// Course completion notification.
$string['completion_notification_subject'] = 'Blerify - Your credential is ready to claim';
$string['completion_notification_body'] = 'Congratulations on completing the course. Your digital credential is ready. Visit the certificate activity in the course to claim it by scanning the QR code with your Blerify Wallet.';

// Capabilities.
$string['blerify:addinstance'] = 'Add a new Blerify Certificate activity';
$string['blerify:view'] = 'View Blerify Certificate';
$string['blerify:manage'] = 'Manage Blerify Certificates';

// Security.
$string['usernotenrolled'] = 'The specified user is not enrolled in this course.';
