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

namespace mod_blerify\client;

defined('MOODLE_INTERNAL') || die();

class client {

    /** @var \curl */
    private $curl;

    /** @var string */
    private $apihost;

    /** @var string */
    private $authapihost;

    /** @var string */
    private $clientid;

    /** @var string */
    private $organizationid;

    /** @var string */
    private $privatekey;

    /** @var string */
    private $iamaudience;

    /** @var string */
    private $tokenuri;

    /** @var string|null Cached access token */
    private $accesstoken;

    /** @var int Token expiration timestamp */
    private $tokenexpiry = 0;

    /** @var string|null Last error */
    public $error;

    public function __construct($curl = null) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $json = get_config('mod_blerify', 'service_account_json');

        if (!empty($json)) {
            $sa = json_decode($json, true);
            if (!$sa || !isset($sa['client_id']) || !isset($sa['private_key'])) {
                throw new \moodle_exception('error_not_configured', 'blerify');
            }

            $this->clientid = $sa['client_id'];
            $this->organizationid = $sa['organization_id'];
            $this->privatekey = $sa['private_key'];
            $this->iamaudience = $sa['iam_audience'];
            $this->tokenuri = $sa['token_uri'];

            $parsed = parse_url($sa['token_uri']);
            $baseurl = $parsed['scheme'] . '://' . $parsed['host'];
            if (!empty($parsed['port'])) {
                $baseurl .= ':' . $parsed['port'];
            }
            $this->apihost = $baseurl;
            $this->authapihost = $baseurl;

        } else {
            $this->clientid = get_config('mod_blerify', 'client_id');
            $this->organizationid = get_config('mod_blerify', 'organization_id');
            $this->privatekey = get_config('mod_blerify', 'private_key');
            $this->apihost = rtrim(get_config('mod_blerify', 'api_host'), '/');
            $this->authapihost = rtrim(get_config('mod_blerify', 'auth_api_host'), '/');
            $this->iamaudience = get_config('mod_blerify', 'iam_audience');
            $this->tokenuri = $this->authapihost . '/auth/v2/protocol/openid-connect/token';
        }

        if (empty($this->clientid) || empty($this->organizationid) || empty($this->privatekey)) {
            throw new \moodle_exception('error_not_configured', 'blerify');
        }

        $this->curl = $curl ? $curl : new \curl();
        $this->curl->setopt(['CURLOPT_TIMEOUT' => 30]);
        $this->error = null;
    }

    public function post($path, $data = []) {
        return $this->request('post', $path, $data);
    }

    public function put($path, $data = []) {
        return $this->request('put', $path, $data);
    }

    public function put_raw($path, $data = []) {
        return $this->request('put', $path, $data, true);
    }

    public function get($path) {
        return $this->request('get', $path);
    }

    public function get_organization_id() {
        return $this->organizationid;
    }

    public function get_api_host() {
        return $this->apihost;
    }

    private function request($method, $path, $data = null, $raw = false) {
        $accesstoken = $this->get_access_token();
        $url = $this->apihost . $path;

        $jsondata = ($data !== null) ? json_encode($data) : null;

        $this->curl->resetHeader();
        $this->curl->setHeader('Authorization: Bearer ' . $accesstoken);
        $this->curl->setHeader('Content-Type: application/json; charset=utf-8');

        $response = $this->curl->$method($url, $jsondata);

        $httpcode = $this->curl->get_info()['http_code'] ?? 0;

        if ($this->curl->error) {
            $this->error = $this->curl->error;
            throw new \Exception('Blerify API error: ' . $this->curl->error);
        }

        if ($httpcode >= 400) {
            $decoded = json_decode($response, true);
            $msg = isset($decoded['message']) ? $decoded['message'] : "HTTP $httpcode";
            throw new \Exception("Blerify API error ($httpcode): $msg");
        }

        if ($response === false || $response === null || $response === '') {
            return null;
        }

        if ($raw) {
            return $response;
        }

        return json_decode($response);
    }

    private function get_access_token() {
        if ($this->accesstoken && $this->tokenexpiry > (time() + 60)) {
            return $this->accesstoken;
        }

        $jwt = $this->create_jwt();

        $postfields = [
            'client_id' => $this->clientid,
            'organization_id' => $this->organizationid,
            'client_assertion' => $jwt,
        ];
        $postdata = http_build_query($postfields, '', '&');

        $authcurl = new \curl();
        $authcurl->setHeader('Content-Type: application/x-www-form-urlencoded');
        $response = $authcurl->post($this->tokenuri, $postdata);

        $httpcode = $authcurl->get_info()['http_code'] ?? 0;

        if ($httpcode !== 200) {
            throw new \Exception("Failed to get access token. HTTP $httpcode");
        }

        $tokendata = json_decode($response, true);
        if (!isset($tokendata['access_token'])) {
            throw new \Exception('Access token not found in auth response.');
        }

        $this->accesstoken = $tokendata['access_token'];
        $expiresin = isset($tokendata['expires_in']) ? (int)$tokendata['expires_in'] : 3600;
        $this->tokenexpiry = time() + $expiresin;

        return $this->accesstoken;
    }

    private function create_jwt() {
        $now = time();

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => $this->clientid,
            'sub' => $this->clientid,
            'aud' => $this->iamaudience,
            'iat' => $now,
            'exp' => $now + 3600,
            'jti' => self::uuid4(),
        ];

        $headerb64 = self::base64url_encode(json_encode($header));
        $payloadb64 = self::base64url_encode(json_encode($payload));

        $signinginput = $headerb64 . '.' . $payloadb64;

        $signature = '';
        $success = openssl_sign($signinginput, $signature, $this->privatekey, OPENSSL_ALGO_SHA256);
        if (!$success) {
            throw new \Exception('Failed to sign JWT: ' . openssl_error_string());
        }

        $signatureb64 = self::base64url_encode($signature);

        return $signinginput . '.' . $signatureb64;
    }

    private static function uuid4() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
