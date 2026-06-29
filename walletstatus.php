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
 * Credential status endpoint for browser polling.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../config.php');

use mod_blerify\wallet\ticket_manager;

require_login(0, false);
require_sesskey();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('blerify', $cmid);
if (!$cm) {
    echo json_encode(['status' => 'none']);
    exit;
}

$context = context_module::instance($cm->id);
if (!is_enrolled($context, $USER->id, '', true)) {
    echo json_encode(['status' => 'none']);
    exit;
}

$manager = new ticket_manager();
$status = $manager->get_ticket_status($USER->id, $cm->instance);

$updated = 0;
$credential = $DB->get_record('blerify_credentials',
    ['blerifyid' => $cm->instance, 'userid' => $USER->id], 'timemodified');
if ($credential) {
    $updated = (int)$credential->timemodified;
}

echo json_encode(['status' => $status, 'updated' => $updated]);
