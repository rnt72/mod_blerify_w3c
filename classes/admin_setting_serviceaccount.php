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
 * Admin setting for Service Account JSON file upload with read-only preview.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom admin setting that allows uploading a JSON file and stores its contents.
 * After saving, a read-only preview is shown with the private_key truncated.
 */
class admin_setting_serviceaccount extends admin_setting {

    /**
     * Constructor.
     *
     * @param string $name Setting name.
     * @param string $visiblename Visible name.
     * @param string $description Description.
     * @param string $defaultsetting Default value.
     */
    public function __construct($name, $visiblename, $description, $defaultsetting = '') {
        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

    /**
     * Return the current setting value.
     *
     * @return mixed
     */
    public function get_setting() {
        return $this->config_read($this->name);
    }

    /**
     * Store the setting value.
     *
     * @param string $data The JSON string to store.
     * @return string Empty on success, error message on failure.
     */
    public function write_setting($data) {
        if (empty($data)) {
            return '';
        }

        $decoded = json_decode($data);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return get_string('setting_service_account_invalid_json', 'blerify');
        }

        $result = $this->config_write($this->name, $data);
        return ($result ? '' : get_string('errorsetting', 'admin'));
    }

    /**
     * Build the HTML for this setting.
     *
     * @param mixed $data Current value.
     * @param string $query Search query (unused).
     * @return string HTML content.
     */
    public function output_html($data, $query = '') {
        $default = $this->get_defaultsetting();
        $current = $this->get_setting();
        $id = $this->get_id();
        $fullname = $this->get_full_name();

        $html = '<div class="form-filemanager" id="' . $id . '_wrapper">';

        $html .= '<input type="hidden" name="' . s($fullname) . '" id="' . $id . '" value="" />';

        $html .= '<div class="mb-2">';
        $html .= '<input type="file" accept=".json" id="' . $id . '_file" class="form-control" />';
        $html .= '<small class="form-text text-muted">' .
                  get_string('setting_service_account_upload_help', 'blerify') .
                  '</small>';
        $html .= '</div>';

        if (!empty($current)) {
            $preview = $this->build_preview($current);
            $html .= '<div class="mb-2" id="' . $id . '_preview_wrapper">';
            $html .= '<label>' . get_string('setting_service_account_current', 'blerify') . '</label>';
            $html .= '<pre class="p-2 border rounded bg-light" '
                    . 'style="max-height:220px;overflow:auto;font-size:0.85em;white-space:pre-wrap;">'
                    . s($preview) . '</pre>';
            $html .= '</div>';
        }

        $html .= '<script>'
            . '(function(){'
            . 'var fileInput=document.getElementById("' . $id . '_file");'
            . 'var hidden=document.getElementById("' . $id . '");'
            . 'fileInput.addEventListener("change",function(){'
            .   'var file=fileInput.files[0];'
            .   'if(!file)return;'
            .   'var reader=new FileReader();'
            .   'reader.onload=function(e){hidden.value=e.target.result;};'
            .   'reader.readAsText(file);'
            . '});'
            . '})();'
            . '</script>';

        $html .= '</div>';

        return format_admin_setting($this, $this->visiblename, $html,
            $this->description, true, '', $default, $query);
    }

    /**
     * Build a safe preview string with private_key truncated.
     *
     * @param string $json Raw JSON string.
     * @return string Formatted preview.
     */
    private function build_preview($json) {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return '(invalid JSON)';
        }

        if (isset($data['private_key'])) {
            $pk = $data['private_key'];
            $data['private_key'] = substr($pk, 0, 40) . '...(truncated)';
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
