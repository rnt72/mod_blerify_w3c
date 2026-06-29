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
 * Exception thrown during the W3C issuance flow, carrying partial progress.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_blerify\apirest;

defined('MOODLE_INTERNAL') || die();

class issuance_exception extends \Exception {

    /** @var string|null Credential id created before the failure, if any. */
    public $credentialid;

    /** @var string Last lifecycle step reached before the failure. */
    public $laststep;

    /**
     * Constructor.
     *
     * @param string $message
     * @param string|null $credentialid
     * @param string $laststep One of 'created', 'signed'.
     * @param \Throwable|null $previous
     */
    public function __construct($message, $credentialid = null, $laststep = '', $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->credentialid = $credentialid;
        $this->laststep = $laststep;
    }
}
