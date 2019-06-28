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
 * myprofile renderer.
 *
 * @package    theme_adaptable
 * @copyright  &copy; 2019 - Coventry University
 * @author     G J Barnard - {@link http://moodle.org/user/profile.php?id=442195}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_adaptable\output\core_user\myprofile;

defined('MOODLE_INTERNAL') || die;

use core_user\output\myprofile\category;
use core_user\output\myprofile\tree;
use html_writer;

/**
 * myprofile renderer.
 */
class renderer extends \core_user\output\myprofile\renderer {
    private $userid = 0;

    function __construct(\moodle_page $page, $target) {
        // We need the user id!
        // From user/profile.php - technically by the time we are instantiated then the user id will have been validated.
        global $USER;
        $userid = optional_param('id', 0, PARAM_INT);
        $this->userid = $userid ? $userid : $USER->id;

        parent::__construct($page, $target);
    }

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
            $categories[$category->name] = $category;
        }

        $output = html_writer::start_tag('div', array('class' => 'profile_tree row'));

        $output .= html_writer::start_tag('div', array('class' => 'col-md-4')); // Col one.

        $output .= html_writer::start_tag('div', array('class' => 'row'));
        foreach ($categorycolone as $categoryname) {
            if (!empty($categories[$categoryname])) {
                $output .= html_writer::start_tag('div', array('class' => 'col-12 '.$categoryname));
                $output .= $markup = $this->render($categories[$categoryname]);
                unset($categories[$categoryname]);
                $output .= html_writer::end_tag('div');
            }
        }
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_tag('div', array('class' => 'col-md-8')); // Col two.
        $output .= html_writer::start_tag('div', array('class' => 'row'));
        $output .= html_writer::start_tag('div', array('class' => 'col-12 '.$categoryname));
        $output .= $this->course($tree);
        $output .= html_writer::end_tag('div');
        /*foreach ($categories as $categoryname => $category) {
            $output .= html_writer::start_tag('div', array('class' => 'col-12 '.$categoryname));
            $output .= $category;
            $output .= html_writer::end_tag('div');
        }*/
        $output .= $this->tabs($categories);

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        $output .= html_writer::tag('p', $this->developer($tree));
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Render a category.
     *
     * @param category $category
     *
     * @return string
     */
    public function render_category(category $category) {
        $nodes = $category->nodes;
        if (empty($nodes)) {
            // No nodes, nothing to render.
            return '';
        }

        $classes = $category->classes;
        if (empty($classes)) {
            $output = html_writer::start_tag('section', array('class' => 'node_category'));
        } else {
            $output = html_writer::start_tag('section', array('class' => 'node_category ' . $classes));
        }
        $output .= html_writer::tag('h3', $category->title);
        $output .= html_writer::start_tag('ul');
        // TODO: Make efficient!
        if ($category->name == 'contact') {
            $output .= $this->userimage();
        }
        foreach ($nodes as $node) {
            $output .= $this->render($node);
        }
        $output .= html_writer::end_tag('ul');
        $output .= html_writer::end_tag('section');

        return $output;
    }

    protected function userimage() {
        $output = '';

        if (!empty($this->userid)) {
            $userpicture = new \user_picture(\core_user::get_user($this->userid));
            $userpicture->size = 1; // Size f1.
            $output .= html_writer::start_tag('li');
            $output .= html_writer::img($userpicture->get_url($this->page)->out(false), 'User image');  // TODO, better 'alt'.
            $output .= html_writer::end_tag('li');
        }

        return $output;
    }

    protected function course() {
        $output = '';

        $output .= html_writer::tag('h1', get_string('module', 'theme_adaptable'));
        $output .= html_writer::tag('p', 'Work in progress - place holder');

        return $output;
    }

    private function developer($tree) {
        $output = html_writer::tag('h1', 'Developer information, please ignore!  Will be removed!');

        $output .= html_writer::start_tag('ul');
        foreach ($tree->categories as $category) {
            $output .= html_writer::start_tag('li');
            $output .= html_writer::tag('p', 'Category - '.$category->name);
            $nodes = $category->nodes;
            if (!empty($nodes)) {
                $output .= html_writer::start_tag('ul');
                foreach ($nodes as $node) {
                    $output .= html_writer::start_tag('li');
                    $output .= html_writer::tag('p', 'Node - '.$node->name);
                    $output .= html_writer::end_tag('li');
                }
                $output .= html_writer::end_tag('ul');
            }
            $output .= html_writer::end_tag('li');
        }
        $output .= html_writer::end_tag('ul');

        return $output;
    }

    protected function tabs($categories) {
        $tabdata = new \stdClass;
        $tabdata->containerid = 'userprofiletabs';
        $tabdata->tabs = array();

        foreach ($categories as $category) {
            $markup = $this->render($category);
            if (!empty($markup)) {
                $tab = new \stdClass;
                $tab->name = $category->name;
                $tab->displayname = $category->title;
                $tab->content = $markup;

                $tabdata->tabs[] = $tab;
            }
        }

        return $this->render_from_template('theme_adaptable/tabs', $tabdata);
    }
}
