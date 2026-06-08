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
 * Activity instance form for mod_blerify.
 * projectId and templateId are loaded automatically from admin config.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_blerify_mod_form extends moodleform_mod {

    /**
     * Define the form elements.
     */
    public function definition() {
        global $DB, $COURSE;

        $mform = $this->_form;

        $config = $DB->get_record('blerify_configs', ['courseid' => $COURSE->id]);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        if (!$config) {
            $mform->addElement('static', 'noconfig', '',
                html_writer::div(get_string('error_no_config_for_course', 'blerify'), 'alert alert-danger'));

            $mform->addElement('hidden', 'name', '');
            $mform->setType('name', PARAM_TEXT);

            $this->standard_coursemodule_elements();
            $this->add_action_buttons(true, false);
            return;
        }

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('header', 'blerifysettings', get_string('blerifysettings', 'blerify'));

        $mform->addElement('static', 'configinfo', get_string('config_name', 'blerify'),
            format_string($config->name));
        $mform->addElement('static', 'projectidinfo', get_string('projectid', 'blerify'),
            html_writer::tag('code', $config->projectid));
        $mform->addElement('static', 'templateidinfo', get_string('templateid', 'blerify'),
            html_writer::tag('code', $config->templateid));

        $mform->addElement('hidden', 'configid', $config->id);
        $mform->setType('configid', PARAM_INT);

        $mform->addElement('checkbox', 'completionissue', get_string('completionissue', 'blerify'));
        $mform->setDefault('completionissue', 1);
        $mform->addHelpButton('completionissue', 'completionissue', 'blerify');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
