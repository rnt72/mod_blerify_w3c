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
 * Admin page to manage Blerify certificate configurations.
 * Allows creating, editing and deleting certificate configs
 * that map courses to Blerify project/template IDs.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('mod_blerify_manage');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_url('/mod/blerify/adminmanage.php');
$PAGE->set_title(get_string('manage_configs', 'blerify'));
$PAGE->set_heading(get_string('manage_configs', 'blerify'));

if ($action === 'delete' && $id && confirm_sesskey()) {
    $DB->delete_records('blerify_configs', ['id' => $id]);
    redirect(new moodle_url('/mod/blerify/adminmanage.php'), get_string('config_deleted', 'blerify'));
}

if ($action === 'save' && data_submitted() && confirm_sesskey()) {
    $data = new stdClass();
    $data->name = required_param('configname', PARAM_TEXT);
    $data->projectid = required_param('projectid', PARAM_TEXT);
    $data->courseid = required_param('courseid', PARAM_INT);
    $data->timemodified = time();

    $uuidpattern = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/';
    if (!preg_match($uuidpattern, $data->projectid)) {
        redirect(new moodle_url('/mod/blerify/adminmanage.php'),
            get_string('error_invalid_uuid', 'blerify'), null, \core\output\notification::NOTIFY_ERROR);
    }

    $editid = optional_param('editid', 0, PARAM_INT);

    if (!$DB->record_exists('course', ['id' => $data->courseid])) {
        redirect(new moodle_url('/mod/blerify/adminmanage.php'),
            get_string('error_course_not_found', 'blerify'), null, \core\output\notification::NOTIFY_ERROR);
    }

    $existing = $DB->get_record('blerify_configs', ['courseid' => $data->courseid]);
    if ($existing && $existing->id != $editid) {
        redirect(new moodle_url('/mod/blerify/adminmanage.php'),
            get_string('error_course_already_configured', 'blerify'), null, \core\output\notification::NOTIFY_ERROR);
    }

    if ($editid) {
        $data->id = $editid;
        $DB->update_record('blerify_configs', $data);
    } else {
        $data->timecreated = time();
        $DB->insert_record('blerify_configs', $data);
    }

    redirect(new moodle_url('/mod/blerify/adminmanage.php'), get_string('config_saved', 'blerify'));
}

$configs = $DB->get_records('blerify_configs', null, 'name ASC');
$editrecord = null;
if ($action === 'edit' && $id) {
    $editrecord = $DB->get_record('blerify_configs', ['id' => $id]);
}

$courses = $DB->get_records('course', ['visible' => 1], 'fullname ASC', 'id, fullname, shortname');
unset($courses[1]);

echo $OUTPUT->header();

echo html_writer::start_tag('div', ['class' => 'mb-4']);
echo html_writer::tag('h4', $editrecord ? get_string('edit_config', 'blerify') : get_string('add_config', 'blerify'));

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => new moodle_url('/mod/blerify/adminmanage.php', ['action' => 'save']),
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
if ($editrecord) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'editid', 'value' => $editrecord->id]);
}

echo html_writer::start_div('form-group row mb-2');
echo html_writer::tag('label', get_string('config_name', 'blerify'), ['class' => 'col-sm-3 col-form-label', 'for' => 'configname']);
echo html_writer::start_div('col-sm-9');
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'configname', 'id' => 'configname',
    'class' => 'form-control', 'required' => 'required',
    'value' => $editrecord ? $editrecord->name : '',
    'placeholder' => get_string('config_name_placeholder', 'blerify'),
]);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('form-group row mb-2');
echo html_writer::tag('label', get_string('projectid', 'blerify'), ['class' => 'col-sm-3 col-form-label', 'for' => 'projectid']);
echo html_writer::start_div('col-sm-9');
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'projectid', 'id' => 'projectid',
    'class' => 'form-control', 'required' => 'required',
    'value' => $editrecord ? $editrecord->projectid : '',
    'placeholder' => get_string('projectid_placeholder', 'blerify'),
]);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('form-group row mb-2');
echo html_writer::tag('label', get_string('course'), ['class' => 'col-sm-3 col-form-label', 'for' => 'courseid']);
echo html_writer::start_div('col-sm-9');
$courseoptions = ['' => get_string('choosedots')];
foreach ($courses as $course) {
    $courseoptions[$course->id] = $course->fullname . ' (' . $course->shortname . ')';
}
echo html_writer::select($courseoptions, 'courseid',
    $editrecord ? $editrecord->courseid : '', false, ['class' => 'form-control', 'required' => 'required']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('form-group row mb-2');
echo html_writer::start_div('col-sm-9 offset-sm-3');
echo html_writer::empty_tag('input', [
    'type' => 'submit', 'value' => get_string('savechanges'),
    'class' => 'btn btn-primary',
]);
if ($editrecord) {
    echo ' ';
    echo html_writer::link(new moodle_url('/mod/blerify/adminmanage.php'),
        get_string('cancel'), ['class' => 'btn btn-secondary']);
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_tag('div');

echo html_writer::tag('h4', get_string('existing_configs', 'blerify'));

if (empty($configs)) {
    echo html_writer::tag('p', get_string('no_configs', 'blerify'), ['class' => 'alert alert-info']);
} else {
    $table = new html_table();
    $table->head = [
        get_string('config_name', 'blerify'),
        get_string('projectid', 'blerify'),
        get_string('course'),
        get_string('actions'),
    ];
    $table->attributes['class'] = 'table table-striped';

    foreach ($configs as $config) {
        $course = $DB->get_record('course', ['id' => $config->courseid], 'fullname, shortname');
        $coursename = $course ? $course->fullname . ' (' . $course->shortname . ')' : get_string('unknowncourse', 'blerify');

        $editurl = new moodle_url('/mod/blerify/adminmanage.php', ['action' => 'edit', 'id' => $config->id]);
        $deleteurl = new moodle_url('/mod/blerify/adminmanage.php', [
            'action' => 'delete', 'id' => $config->id, 'sesskey' => sesskey()
        ]);

        $actions = html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-sm btn-outline-primary mr-1']);
        $actions .= ' ';
        $actions .= html_writer::link($deleteurl, get_string('delete'),
            ['class' => 'btn btn-sm btn-outline-danger blerify-delete-config',
             'data-confirm' => get_string('config_delete_confirm', 'blerify')]);

        $table->data[] = [
            format_string($config->name),
            html_writer::tag('code', $config->projectid),
            $coursename,
            $actions,
        ];
    }
    echo html_writer::table($table);
}

$PAGE->requires->js_call_amd('mod_blerify/admin_manage', 'init');

echo $OUTPUT->footer();
