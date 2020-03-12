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

namespace theme_adaptable\output;

defined('MOODLE_INTERNAL') || die();

/**
 * The mustache renderer.
 *
 * @copyright  &copy; 2020-onwards G J Barnard.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class mustache_renderer extends \renderer_base {

    /**
     * @var Mustache_Engine $mustache The mustache template compiler.
     */
    protected $mustache;

    /**
     * Return an instance of the mustache class.
     *
     * @since 2.9
     * @return Mustache_Engine
     */
    protected function get_mustache() {
        global $CFG;

        if ($this->mustache === null) {
            require_once("{$CFG->libdir}/filelib.php");

            $themename = $this->page->theme->name;
            $themerev = theme_get_revision();

            // Create new localcache directory.
            $cachedir = make_localcache_directory("mustache/$themerev/$themename");

            // Remove old localcache directories.
            $mustachecachedirs = glob("{$CFG->localcachedir}/mustache/*", GLOB_ONLYDIR);
            foreach ($mustachecachedirs as $localcachedir) {
                $cachedrev = [];
                preg_match("/\/mustache\/([0-9]+)$/", $localcachedir, $cachedrev);
                $cachedrev = isset($cachedrev[1]) ? intval($cachedrev[1]) : 0;
                if ($cachedrev > 0 && $cachedrev < $themerev) {
                    fulldelete($localcachedir);
                }
            }

            $loader = new \theme_adaptable\output\mustache_setting_string_loader();
            $stringhelper = new \core\output\mustache_string_helper();
            $quotehelper = new \core\output\mustache_quote_helper();
            $jshelper = new \core\output\mustache_javascript_helper($this->page);
            $pixhelper = new \core\output\mustache_pix_helper($this);
            $shortentexthelper = new \core\output\mustache_shorten_text_helper();
            $userdatehelper = new \core\output\mustache_user_date_helper();

            // We only expose the variables that are exposed to JS templates.
            $safeconfig = $this->page->requires->get_config_for_javascript($this->page, $this);

            $helpers = array('config' => $safeconfig,
                             'str' => array($stringhelper, 'str'),
                             'quote' => array($quotehelper, 'quote'),
                             'js' => array($jshelper, 'help'),
                             'pix' => array($pixhelper, 'pix'),
                             'shortentext' => array($shortentexthelper, 'shorten'),
                             'userdate' => array($userdatehelper, 'transform'),
                         );

            $this->mustache = new \core\output\mustache_engine(array(
                'cache' => $cachedir,
                'escape' => 's',
                'loader' => $loader,
                'helpers' => $helpers,
                'pragmas' => [\Mustache_Engine::PRAGMA_BLOCKS],
                // Don't allow the JavaScript helper to be executed from within another
                // helper. If it's allowed it can be used by users to inject malicious
                // JS into the page.
                'blacklistednestedhelpers' => ['js']));

        }

        return $this->mustache;
    }

}