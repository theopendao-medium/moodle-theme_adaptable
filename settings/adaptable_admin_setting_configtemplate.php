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

    private $themesetting;

    /**
     * Config template constructor
     *
     * @param string $name unique ascii name, either 'mysetting' for settings that in config, or 'myplugin/mysetting' for ones in
     * config_plugins.
     * @param string $visiblename localised
     * @param string $description long localised info
     * @param string $defaultsetting
     */
    public function __construct($name, $visiblename, $description, $defaultsetting, $themesetting) {
        global $PAGE;
        $PAGE->requires->js_call_amd('theme_adaptable/templatepreview', 'init');

        $this->themesetting = $themesetting;

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

        $overridetemplate = get_config('theme_adaptable', $this->themesetting);
        if (!empty($overridetemplate)) {
            global $PAGE;

            $renderer = $PAGE->get_renderer('theme_adaptable', 'mustache');

            //$data = new \stdClass;
            preg_match('/Example context \(json\):([\s\S]*)/', $overridetemplate, $matched);  // From 'display.js' in the template tool.
            $json = trim(substr($matched[1], 0, strpos($matched[1], '}}')));
            $data = json_decode($json);

error_log('MAT:'.print_r($matched[1], true));
error_log('JSON:'.print_r($json, true));
error_log('DAT:'.print_r($data, true));

            $element = $renderer->render_from_template($overridetemplate, $data);
        } else {
            $element = '';
        }

        return format_admin_setting($this, $this->visiblename, $element, $this->description, true, '', $defaultinfo, $query);
    }
}
