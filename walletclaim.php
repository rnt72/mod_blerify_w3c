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
 * Wallet claim endpoint — external API called by the Blerify Wallet app.
 *
 * URL: walletclaim.php/{token}
 * POST body: {didJwt}  |  Header: X-OTP
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

require_once('../../config.php');

use mod_blerify\wallet\ticket_manager;
use mod_blerify\local\credentials;

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$allowedorigins = ['https://demo.wallet.blerify.com', 'https://wallet.blerify.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedorigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-OTP');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['data' => ['success' => false, 'message' => 'method_not_allowed']]);
    exit;
}

$cache = \cache::make('mod_blerify', 'ratelimit');
$ip = getremoteaddr();
$ratelimitkey = 'walletclaim_' . sha1($ip);
$attempts = (int)($cache->get($ratelimitkey) ?: 0);
if ($attempts > 20) {
    \mod_blerify\event\claim_rate_limited::create([
        'context' => \context_system::instance(),
        'other' => ['iphash' => sha1($ip)],
    ])->trigger();
    http_response_code(429);
    echo json_encode(['data' => ['success' => false, 'message' => 'rate_limited']]);
    exit;
}
$cache->set($ratelimitkey, $attempts + 1);

$pathinfo = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
if (empty($pathinfo)) {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#/walletclaim\.php/([a-f0-9]{64})#', $uri, $matches)) {
        $pathinfo = $matches[1];
    }
}

$token = $pathinfo;

if (strlen($token) !== 64 || !ctype_xdigit($token)) {
    http_response_code(404);
    echo json_encode(['data' => ['success' => false, 'message' => 'not_found']]);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE || !$data || empty($data['didJwt'])) {
    http_response_code(400);
    echo json_encode(['data' => ['success' => false, 'message' => 'missing_didJwt']]);
    exit;
}

$didjwt = trim($data['didJwt']);

if (!preg_match('/^did:[a-z0-9]+:[a-zA-Z0-9._:%-]{1,480}$/', $didjwt)) {
    http_response_code(400);
    echo json_encode(['data' => ['success' => false, 'message' => 'invalid_did_format']]);
    exit;
}

$otp = '';
if (isset($_SERVER['HTTP_X_OTP'])) {
    $otp = $_SERVER['HTTP_X_OTP'];
} else if (function_exists('getallheaders')) {
    $headers = getallheaders();
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'x-otp') {
            $otp = $value;
            break;
        }
    }
}

if (empty($otp) || strlen($otp) > 10) {
    http_response_code(400);
    echo json_encode(['data' => ['success' => false, 'message' => 'missing_otp']]);
    exit;
}

$manager = new ticket_manager();
$result = $manager->validate_claim($token, $otp);

if (!$result['success']) {
    if (!empty($result['blerifyid'])) {
        $cm = get_coursemodule_from_instance('blerify', $result['blerifyid']);
        $eventcontext = $cm ? \context_module::instance($cm->id) : \context_system::instance();
        $eventdata = [
            'context' => $eventcontext,
            'relateduserid' => $result['userid'],
        ];
        if (!empty($result['locked']) || $result['error'] === 'too_many_attempts') {
            \mod_blerify\event\otp_lockout::create($eventdata)->trigger();
        } else if ($result['error'] === 'invalid_otp') {
            \mod_blerify\event\otp_failed::create($eventdata)->trigger();
        }
    }

    $httpcode = 403;
    if ($result['error'] === 'invalid_token') {
        $httpcode = 404;
    } else if ($result['error'] === 'token_expired') {
        $httpcode = 410;
    }
    http_response_code($httpcode);
    echo json_encode(['data' => ['success' => false, 'message' => $result['error']]]);
    exit;
}

$userid = $result['userid'];
$blerifyid = $result['blerifyid'];

$user = $DB->get_record('user', ['id' => $userid]);
if (!$user) {
    http_response_code(500);
    echo json_encode(['data' => ['success' => false, 'message' => 'user_not_found']]);
    exit;
}

$blerify = $DB->get_record('blerify', ['id' => $blerifyid]);
if (!$blerify) {
    http_response_code(500);
    echo json_encode(['data' => ['success' => false, 'message' => 'activity_not_found']]);
    exit;
}

$config = $DB->get_record('blerify_configs', ['id' => $blerify->configid]);
if (!$config) {
    http_response_code(500);
    echo json_encode(['data' => ['success' => false, 'message' => 'config_not_found']]);
    exit;
}

$claimcm = get_coursemodule_from_instance('blerify', $blerifyid);
$claimcontext = $claimcm ? \context_module::instance($claimcm->id) : \context_system::instance();

if (!$claimcm || !is_enrolled($claimcontext, $userid)) {
    http_response_code(403);
    echo json_encode(['data' => ['success' => false, 'message' => 'not_enrolled']]);
    exit;
}

$credentialsmanager = new credentials();
$existing = $credentialsmanager->get_credential_for_user($blerifyid, $userid);
$wasassembled = ($existing && $existing->status === 'assembled');

$transaction = $DB->start_delegated_transaction();
try {
    $manager->store_did($userid, $didjwt);
    $transaction->allow_commit();
} catch (\Exception $e) {
    try {
        $transaction->rollback($e);
    } catch (\Exception $ignored) {
        $ignored = null;
    }
    http_response_code(500);
    echo json_encode(['data' => ['success' => false, 'message' => 'did_store_failed']]);
    exit;
}

\mod_blerify\event\did_linked::create([
    'context' => $claimcontext,
    'relateduserid' => $userid,
])->trigger();

try {
    if ($existing) {
        $issueresult = $credentialsmanager->reissue_credential_w3c($blerify, $config, $user, $didjwt, $existing);
    } else {
        $issueresult = $credentialsmanager->issue_credential_w3c($blerify, $config, $user, $didjwt);
    }

    if ($wasassembled) {
        \mod_blerify\event\credential_reissued::create([
            'context' => $claimcontext,
            'relateduserid' => $userid,
        ])->trigger();
    } else {
        \mod_blerify\event\claim_succeeded::create([
            'context' => $claimcontext,
            'relateduserid' => $userid,
        ])->trigger();
    }

    $assembleraw = $issueresult['assemble_raw'] ?? '';
    http_response_code(200);
    echo json_encode(['data' => [
        'success' => true,
        'message' => 'credential successfully created',
        'assemble' => !empty($assembleraw) ? bin2hex($assembleraw) : '',
    ]]);

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['data' => [
        'success' => false,
        'message' => 'credential_issuance_failed',
    ]]);
}
