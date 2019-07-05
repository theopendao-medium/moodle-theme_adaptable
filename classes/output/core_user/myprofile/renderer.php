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
use core_user\output\myprofile\node;
use core_user\output\myprofile\tree;
use html_writer;

require_once($CFG->dirroot.'/user/lib.php');

/**
 * myprofile renderer.
 */
class renderer extends \core_user\output\myprofile\renderer {
    private $user = null;
    private $course = null;

    function __construct(\moodle_page $page, $target) {
        /* We need the user id!
           From user/profile.php - technically by the time we are instantiated then the user id will have been validated. */
        global $CFG, $DB, $USER;
        $userid = optional_param('id', 0, PARAM_INT);
        $userid = $userid ? $userid : $USER->id;
        $this->user = \core_user::get_user($userid);

        $courseid = optional_param('course', SITEID, PARAM_INT); // Course id (defaults to Site).
        $this->course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

        /* Using this as the function copes with hidden fields and capabilities.  For example:
         * If the you're allowed to see the description.
         *
         * This way because the DB user record from get_user can contain the description that
         * the function user_get_user_details can exclude! */
        $this->user->userdetails = user_get_user_details($this->user, $this->course);

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
        $categories = array();
        foreach ($tree->categories as $category) {
            $categories[$category->name] = $category;
        }

        $output = html_writer::start_tag('div', array('id' => 'adaptable_profile_tree', 'class' => 'profile_tree row'));

        $output .= html_writer::start_tag('div', array('class' => 'ucol1 col-md-4')); // Col one.

        $output .= html_writer::start_tag('div', array('class' => 'row'));
        $output .= html_writer::start_tag('div', array('class' => 'col-12 contact'));
        $contactcategory = $this->transform_contact_category($categories['contact']);
        $output .= $this->render($contactcategory);
        unset($categories['contact']);
        $output .= html_writer::end_tag('div');

        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('div');

        $output .= html_writer::start_tag('div', array('class' => 'ucol2 col-md-8')); // Col two.
        $output .= $this->tabs($categories, $tree);
        $output .= html_writer::end_tag('div');

        //$output .= html_writer::tag('p', $this->developer($tree));
        $output .= html_writer::end_tag('div');

        return $output;
    }

    protected function transform_contact_category($oldcontactcategory) {
        $contactcategory = new category('contact', '');

        $node = new node('contact', 'userimage', '', null, null,
            $this->userimage());
        $contactcategory->add_node($node);

        $node = new node('contact', 'firstname', '', null, null,
            $this->user->userdetails['firstname']);
        $contactcategory->add_node($node);

        $node = new node('contact', 'lastname', '', null, null,
            $this->user->userdetails['lastname']);
        $contactcategory->add_node($node);

        $contactcategory->add_node($oldcontactcategory->nodes['editprofile']);

        if (!empty($this->user->userdetails['email'])) {
            $node = new node('contact', 'email', '', null, null,
                $this->user->userdetails['email']);
            $contactcategory->add_node($node);
        }

        $messagebuttons = $this->message_user();
        if (!empty($messagebuttons)) {
            foreach($messagebuttons as $button) {
                $node = new node('contact', $button['type'], '', null, null, $button['content']);
                $contactcategory->add_node($node);
            }
        }

        return $contactcategory;
    }

    protected function message_user() {
        global $CFG, $PAGE, $USER;
        $output = array();

        $course = ($PAGE->context->contextlevel == CONTEXT_COURSE) ? $PAGE->course : null;  // Use Moodle code just in case!
        $user = $this->user;

        if (user_can_view_profile($user, $course)) {
            $context = \context_user::instance($user->id, IGNORE_MISSING);
            // Check to see if we can display a message button.
            if (!empty($CFG->messaging) && has_capability('moodle/site:sendmessage', $context)) {
                $userbuttons = array(
                    'messages' => array(
                    'buttontype' => 'message',
                    'title' => get_string('message', 'message'),
                    'url' => new \moodle_url('/message/index.php', array('id' => $user->id)),
                    'image' => 't/message',
                    'linkattributes' => \core_message\helper::messageuser_link_params($user->id),
                    'page' => $PAGE
                    )
                );
                \core_message\helper::messageuser_requirejs();

                if ($USER->id != $user->id) {
                    $iscontact = \core_message\api::is_contact($USER->id, $user->id);
                    $contacttitle = $iscontact ? 'removefromyourcontacts' : 'addtoyourcontacts';
                    $contacturlaction = $iscontact ? 'removecontact' : 'addcontact';
                    $contactimage = $iscontact ? 'removecontact' : 'addcontact';
                    $userbuttons['togglecontact'] = array(
                        'buttontype' => 'togglecontact',
                        'title' => get_string($contacttitle, 'message'),
                        'url' => new \moodle_url('/message/index.php', array(
                                'user1' => $USER->id,
                                'user2' => $user->id,
                                $contacturlaction => $user->id,
                                'sesskey' => sesskey())
                        ),
                        'image' => 't/'.$contactimage,
                        'linkattributes' => \core_message\helper::togglecontact_link_params($user, $iscontact),
                        'page' => $PAGE
                    );
                    \core_message\helper::togglecontact_requirejs();
                }

                $PAGE->requires->string_for_js('changesmadereallygoaway', 'moodle');
                foreach ($userbuttons as $button) {
                    $image = $this->pix_icon($button['image'], $button['title'], 'moodle', array(
                        'class' => 'iconsmall',
                        'role' => 'presentation'
                    ));
                    $image .= html_writer::span($button['title'], 'header-button-title');
                    $output[] = array(
                        'type' => $button['buttontype'],
                        'content' => html_writer::link($button['url'], html_writer::tag('span', $image), $button['linkattributes'])
                    );
                }
            }
        }

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
            $output = html_writer::start_tag('section', array('class' => 'node_category '.$category->name));
        } else {
            $output = html_writer::start_tag('section', array('class' => 'node_category '.$category->name.' '.$classes));
        }
        if ((empty($category->notitle)) && ($category->title)) {
            $output .= html_writer::tag('h3', $category->title);
        }
        $output .= html_writer::start_tag('ul');
        foreach ($nodes as $node) {
            $output .= $this->render($node);
        }
        $output .= html_writer::end_tag('ul');
        $output .= html_writer::end_tag('section');

        return $output;
    }

    /**
     * Render a node.
     *
     * @param node $node
     *
     * @return string
     */
    public function render_node(node $node) {
        $return = '';
        if (is_object($node->url)) {
            $header = \html_writer::link($node->url, $node->title);
        } else {
            $header = $node->title;
        }
        $icon = $node->icon;
        if (!empty($icon)) {
            $header .= $this->render($icon);
        }
        $content = $node->content;
        $classes = $node->classes;
        if (!empty($content)) {
            if ($header) {
                // There is some content to display below this make this a header.
                $return = \html_writer::tag('dt', $header);
                $return .= \html_writer::tag('dd', $content);

                $return = \html_writer::tag('dl', $return);
            } else {
                $return = \html_writer::span($content);
            }
            if ($classes) {
                $return = \html_writer::tag('li', $return, array('class' => 'contentnode '.$node->name.' '.$classes));
            } else {
                $return = \html_writer::tag('li', $return, array('class' => 'contentnode '.$node->name));
            }
        } else {
            $return = \html_writer::span($header);
            $return = \html_writer::tag('li', $return, array('class' => $classes));
        }

        return $return;
    }

    protected function userimage() {
        $output = '';

        if (!empty($this->user)) {
            global $OUTPUT;
            $output .= html_writer::start_tag('li', array('class' => 'adaptableuserpicture'));
            $output .= $OUTPUT->user_picture($this->user, array('size' => '1'));
            $output .= html_writer::end_tag('li');
        }

        return $output;
    }

    protected function create_aboutme($tree) {
        global $OUTPUT;

        $aboutme = new category('aboutme', get_string('aboutme', 'theme_adaptable'));

        // Description.
        if (!empty($this->user->userdetails['description'])) {
            $node = new node('aboutme', 'description', get_string('description'), null, null,
                $this->user->userdetails['description']);
            $aboutme->add_node($node);
        }

        // Interests.
        if (!empty($this->user->userdetails['interests'])) {
            $node = new node('aboutme', 'interests', get_string('interests'), null, null,
                $OUTPUT->tag_list(\core_tag_tag::get_item_tags('core', 'user', $this->user->id), '')); // Odd but just the way things can be!
            $aboutme->add_node($node);
        }

        return $aboutme;
    }

    protected function customuserprofile() {
        $category = null;

        $customcoursetitleprofilefield = get_config('theme_adaptable', 'customcoursetitle');
        $customcoursesubtitleprofilefield = get_config('theme_adaptable', 'customcoursesubtitle');

        if ((!empty($customcoursetitleprofilefield)) || (!empty($customcoursesubtitleprofilefield))) {
            $customcoursetitle = '';
            $customcoursesubtitle = '';
            $searcharray = array();
            foreach($this->user->userdetails['customfields'] as $cfield) {
                $searcharray[$cfield['shortname']] = $cfield;
            }

            if (!empty($customcoursetitleprofilefield)) {
                if (array_key_exists($customcoursetitleprofilefield, $searcharray)) {
                    $customcoursetitle = $searcharray[$customcoursetitleprofilefield]['value'];
                }
            }
            if (!empty($customcoursesubtitleprofilefield)) {
                if (array_key_exists($customcoursesubtitleprofilefield, $searcharray)) {
                    $customcoursesubtitle = $searcharray[$customcoursesubtitleprofilefield]['value'];
                }
            }

            if ((!empty($customcoursetitle)) || (!empty($customcoursesubtitle))) {
                $category = new category('customuserprofile', get_string('course', 'theme_adaptable'));

                if (!empty($customcoursetitle)) {
                    $node = new node('customuserprofile', 'customcoursetitle', '', null, null, $customcoursetitle);
                    $category->add_node($node);
                }
                if (!empty($customcoursesubtitle)) {
                    $node = new node('customuserprofile', 'customcoursesubtitle', '', null, null, $customcoursesubtitle);
                    $category->add_node($node);
                }
            }
        }

        return $category;
    }

    protected function create_editprofile() {
        $editprofile = new category('editprofile', 'Edit profile');

        $editprofileform = editprofile::generate_form();
        $node = new node('editprofile', 'editprofile', '', null, null, $editprofileform['form']->render());
        $editprofile->add_node($node);

        // Process the form.
        editprofile::process_form(true, $editprofileform);

        return $editprofile;
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

    protected function tabs($categories, $tree) {
        static $tabcategories = array('coursedetails');

        $tabdata = new \stdClass;
        $tabdata->containerid = 'userprofiletabs';
        $tabdata->tabs = array();

        // Aboutme tab.
        $category = $this->create_aboutme($tree);
        $tab = new \stdClass;
        $tab->name = $category->name;
        $tab->displayname = $category->title;
        $customuserprofilecat = $this->customuserprofile();
        $tab->content = '';
        if (!is_null($customuserprofilecat)) {
            $tab->content .= $this->render($customuserprofilecat);
        } else {
            $category->notitle = true;
        }
        $tab->content .= $this->render($category);
        $tabdata->tabs[] = $tab;

        foreach ($tabcategories as $categoryname) {
            if (!empty($categories[$categoryname])) {
                $category = $categories[$categoryname];
                if ($category->name == 'coursedetails') {
                    $category->notitle = true;
                }
                $markup = $this->render($category);
                if (!empty($markup)) {
                    $tab = new \stdClass;
                    $tab->name = $category->name;
                    if ($category->name == 'coursedetails') {
                        $tab->displayname = get_string('courses', 'theme_adaptable');
                    } else {
                        $tab->displayname = $category->title;
                    }
                    $tab->content = $markup;
                    $tabdata->tabs[] = $tab;
                }
                unset($categories[$categoryname]);
            }
        }

        // More tab.
        $misccontent = html_writer::start_tag('div', array('class' => 'row'));
        foreach ($categories as $categoryname => $category) {
            $misccontent .= html_writer::start_tag('div', array('class' => 'col-12 '.$categoryname));
            $misccontent .= $this->render($category);
            $misccontent .= html_writer::end_tag('div');
        }
        $misccontent .= html_writer::end_tag('div');
        $tab = new \stdClass;
        $tab->name = 'more';
        $tab->displayname = get_string('more', 'theme_adaptable');
        $tab->content = $misccontent;
        $tabdata->tabs[] = $tab;

        // Edit profile tab.
        $category = $this->create_editprofile();
        $tab = new \stdClass;
        $tab->name = $category->name;
        $tab->displayname = $category->title;
        $category->notitle = true;
        $tab->content = $this->render($category);
        $tabdata->tabs[] = $tab;

        return $this->render_from_template('theme_adaptable/tabs', $tabdata);
    }
}
