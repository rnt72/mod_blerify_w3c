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
 * Admin settings for mod_blerify
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('modsettings', new admin_externalpage(
    'mod_blerify_manage',
    get_string('manage_configs', 'blerify'),
    new moodle_url('/mod/blerify/adminmanage.php')
));

require_once($CFG->dirroot . '/mod/blerify/classes/admin_setting_serviceaccount.php');

$settings->add(
    new admin_setting_serviceaccount(
        'mod_blerify/service_account_json',
        get_string('setting_service_account_json', 'blerify'),
        get_string('setting_service_account_json_desc', 'blerify'),
        ''
    )
);

