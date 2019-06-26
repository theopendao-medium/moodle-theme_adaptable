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
 * Version details
 *
 * @package    theme_adaptable
 * @copyright  &copy; 2019 - Coventry University
 * @author     G J Barnard - {@link http://moodle.org/user/profile.php?id=442195}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace theme_adaptable\output\core_user\myprofile;

defined('MOODLE_INTERNAL') || die;

use core_user\output\myprofile\tree;
use html_writer;

/**
 * myprofile renderer.
 */
class renderer extends \core_user\output\myprofile\renderer {

    /**
     * Render the whole tree.
     *
     * @param tree $tree
     *
     * @return string
     */
    public function render_tree(tree $tree) {
        static $categorycolone = array('contact');
        $categories = array();
        foreach ($tree->categories as $category) {
            $markup = $this->render($category);
            if (!empty($markup)) {
                $categories[$category->name] = $markup;
            }
        }

        $output = html_writer::start_tag('div', array('class' => 'profile_tree row'));

        $output .= html_writer::start_tag('div', array('class' => 'col-md-4')); // Col one.

        $output .= html_writer::start_tag('div', array('class' => 'row'));
        foreach ($categorycolone as $categoryname) {
            if (!empty($categories[$categoryname])) {
                $output .= html_writer::start_tag('div', array('class' => 'col-12 '.$categoryname));
                $output .= $categories[$categoryname];
                unset($categories[$categoryname]);
                $output .= html_writer::end_tag('div');
            }
        }
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_tag('div', array('class' => 'col-md-8')); // Col two.
        $output .= html_writer::start_tag('div', array('class' => 'row'));
        foreach ($categories as $categoryname => $category) {
            $output .= html_writer::start_tag('div', array('class' => 'col-12 '.$categoryname));
            $output .= $category;
            $output .= html_writer::end_tag('div');
        }
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');

        return $output;
    }

}
