<?php
// This file is part of Moodle - http://moodle.org/
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
 * @package    theme_adaptable
 * @copyright  2020 Gareth J Barnard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

class adaptable_admin_setting_configtemplate extends admin_setting_configtextarea {

    /**
     * @param string $name
     * @param string $visiblename
     * @param string $description
     * @param mixed $defaultsetting string or array
     * @param mixed $paramtype
     * @param string $cols The number of columns to make the editor
     * @param string $rows The number of rows to make the editor
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, $paramtype=PARAM_RAW, $cols='60', $rows='8') {
        $this->rows = $rows;
        $this->cols = $cols;

        global $PAGE;
        $PAGE->requires->js_call_amd('theme_adaptable/templatepreview', 'init');

        parent::__construct($name, $visiblename, $description, $defaultsetting);
    }

    /**
     * Returns an XHTML string for the editor
     *
     * @param string $data
     * @param string $query
     * @return string XHTML string for the editor
     */
    public function output_html($data, $query='') {
        global $OUTPUT;

        $default = $this->get_defaultsetting();
        $defaultinfo = $default;
        if (!is_null($default) and $default !== '') {
            $defaultinfo = "\n".$default;
        }

        $context = (object) [
            'cols' => $this->cols,
            'rows' => $this->rows,
            'id' => $this->get_id(),
            'name' => $this->get_full_name(),
            'value' => $data,
            'forceltr' => $this->get_force_ltr(),
        ];
        $element = $OUTPUT->render_from_template('core_admin/setting_configtextarea', $context);

        $element = format_admin_setting($this, $this->visiblename, $element, $this->description, true, '', $defaultinfo, $query);

        $overridetemplate = get_config('theme_adaptable', $this->name);

        if (!empty($overridetemplate)) {
            global $PAGE;

            $renderer = $PAGE->get_renderer('theme_adaptable', 'mustache');

            preg_match('/Example context \(json\):([\s\S]*)/', $overridetemplate, $matched);  // From 'display.js' in the template tool.

            if (!empty($matched[1])) {
                $json = trim(substr($matched[1], 0, strpos($matched[1], '}}')));
                $data = json_decode($json);

                $context = (object) [
                    'templatetitle' => $this->visiblename,
                    'templatepreview' => $renderer->render_from_template($overridetemplate, $data)
                ];
                $element .= $OUTPUT->render_from_template('theme_adaptable/adaptable_admin_setting_configtemplate', $context);
            } else {
                $context = array();
                $element .= $OUTPUT->render_from_template('theme_adaptable/adaptable_admin_setting_configtemplate_nopreview', $context);
            }
        }

        return $element;
    }
}
