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
 * Serves a learner's credential PDF or thumbnail.
 *
 * Blerify returns signed URLs that expire after about a minute, so they are
 * requested on demand and streamed through Moodle rather than handed to the
 * browser, which also keeps the signature out of the page.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/filelib.php');

use mod_blerify\local\credentials;

$cmid = required_param('id', PARAM_INT);
$asset = optional_param('asset', 'pdf', PARAM_ALPHA);
$download = optional_param('download', 0, PARAM_BOOL);
$userid = optional_param('userid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('blerify', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/blerify:view', $context);

// Teachers may fetch any learner's certificate; learners only their own.
if ($userid && $userid !== (int)$USER->id) {
    require_capability('mod/blerify:manage', $context);
} else {
    $userid = (int)$USER->id;
}

if (!in_array($asset, ['pdf', 'thumbnail'], true)) {
    throw new moodle_exception('invalidparameter', 'error');
}

$manager = new credentials();
$credential = $manager->get_credential_for_user($cm->instance, $userid);

if (!$credential || !in_array($credential->status,
        [credentials::STATUS_ISSUED, credentials::STATUS_CLAIMED], true)) {
    throw new moodle_exception('error_credential_not_ready', 'blerify');
}

$urls = $manager->get_asset_urls($credential);
$url = $urls[$asset];

if (empty($url) || strpos($url, 'https://') !== 0) {
    throw new moodle_exception('error_credential_not_ready', 'blerify');
}

$curl = new \curl();
$curl->setopt([
    'CURLOPT_TIMEOUT' => 30,
    'CURLOPT_FOLLOWLOCATION' => false,
    'CURLOPT_SSL_VERIFYPEER' => true,
    'CURLOPT_SSL_VERIFYHOST' => 2,
]);

$content = $curl->get($url);

if ($curl->error || ($curl->get_info()['http_code'] ?? 0) !== 200 || $content === '') {
    throw new moodle_exception('error_credential_not_ready', 'blerify');
}

$ispdf = ($asset === 'pdf');
$filename = clean_filename(format_string($cm->name)) . ($ispdf ? '.pdf' : '.png');

header('Content-Type: ' . ($ispdf ? 'application/pdf' : 'image/png'));
header('Content-Length: ' . strlen($content));
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') .
    '; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=30');

echo $content;
