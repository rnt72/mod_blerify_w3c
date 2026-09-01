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
 * The project comes from the course configuration; the teacher picks the
 * template to issue from the ones available in that project.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/blerify/locallib.php');


class mod_blerify_mod_form extends moodleform_mod {

    /**
     * Define the form elements.
     */
    public function definition() {
        global $COURSE;

        $mform = $this->_form;

        $projectid = blerify_get_project_id($COURSE->id);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        if ($projectid === '') {
            $this->add_blocked_form(get_string('error_no_project_id', 'blerify'));
            return;
        }

        try {
            $templates = blerify_get_templates($projectid);
        } catch (\Exception $e) {
            debugging('Blerify: could not list templates: ' . $e->getMessage(), DEBUG_DEVELOPER);
            $this->add_blocked_form(get_string('error_templates_unavailable', 'blerify'));
            return;
        }

        if (empty($templates)) {
            $this->add_blocked_form(get_string('error_no_templates', 'blerify'));
            return;
        }

        $mform->addElement('text', 'name', get_string('certificatename', 'blerify'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'certificatename', 'blerify');

        $mform->addElement('header', 'blerifysettings', get_string('blerifysettings', 'blerify'));

        $options = [];
        foreach ($templates as $template) {
            $options[$template['id']] = $template['title'];
        }
        $mform->addElement('select', 'templateid', get_string('templatetoissue', 'blerify'), $options);
        $mform->addRule('templateid', null, 'required', null, 'client');
        $mform->addHelpButton('templateid', 'templatetoissue', 'blerify');

        // Keeps the chosen title available for display without calling the API again.
        $mform->addElement('hidden', 'templatename', '');
        $mform->setType('templatename', PARAM_TEXT);

        $mform->addElement('static', 'projectidinfo', get_string('projectid', 'blerify'),
            html_writer::tag('code', $projectid));

        $mform->addElement('checkbox', 'completionissue', get_string('completionissue', 'blerify'));
        $mform->setDefault('completionissue', 1);
        $mform->addHelpButton('completionissue', 'completionissue', 'blerify');

        $mform->addElement('text', 'passgrade', get_string('passgrade', 'blerify'), ['size' => '4']);
        $mform->setType('passgrade', PARAM_INT);
        $mform->setDefault('passgrade', 70);
        $mform->addHelpButton('passgrade', 'passgrade', 'blerify');
        $mform->hideIf('passgrade', 'completionissue', 'notchecked');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Render a form that only explains why the activity cannot be configured.
     *
     * @param string $message The reason shown to the teacher.
     */
    private function add_blocked_form($message) {
        $mform = $this->_form;

        $mform->addElement('static', 'noconfig', '',
            html_writer::div($message, 'alert alert-danger'));

        $mform->addElement('hidden', 'name', '');
        $mform->setType('name', PARAM_TEXT);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons(true, false);
    }

    /**
     * Store the title of the selected template alongside its id.
     *
     * @param array $data
     * @param array $files
     * @return array Validation errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (isset($data['passgrade']) && ($data['passgrade'] < 0 || $data['passgrade'] > 100)) {
            $errors['passgrade'] = get_string('error_passgrade_range', 'blerify');
        }

        return $errors;
    }
}
