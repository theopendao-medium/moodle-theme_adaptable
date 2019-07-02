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

/**
 * myprofile renderer.
 */
class renderer extends \core_user\output\myprofile\renderer {
    private $user = null;
    private $course = null;

    function __construct(\moodle_page $page, $target) {
        // We need the user id!
        // From user/profile.php - technically by the time we are instantiated then the user id will have been validated.
        global $CFG, $DB, $USER;
        $userid = optional_param('id', 0, PARAM_INT);
        $userid = $userid ? $userid : $USER->id;
        $this->user = \core_user::get_user($userid);

        //require_once($CFG->dirroot.'/user/profile/lib.php');
        //profile_load_data($this->user);
        //$this->user->interests = \core_tag_tag::get_item_tags('core', 'user', $this->user->id);

        $courseid = optional_param('course', SITEID, PARAM_INT); // Course id (defaults to Site).
        $this->course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

        require_once($CFG->dirroot.'/user/lib.php');
        /* Using this as the function copes with hidden fields and capabilities.  For example:
         * If the you're allowed to see the description.
         *
         * This way because the DB user record from get_user can contain the description that
         * the function user_get_user_details can exclude! */
        $this->user->userdetails = user_get_user_details($this->user, $this->course);
//error_log(print_r($userdetails, true));
        /*foreach($userdetails as $detailname => $detail) {
            if (empty($this->user->$detailname)) {
                $this->user->$detailname = $detail;
            }
        }*/
//error_log(print_r($this->user, true));

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
//        error_log(print_r($tree, true));
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
        $output .= $this->tabs($categories, $tree);

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

        if (!empty($this->user)) {
            $userpicture = new \user_picture($this->user);
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
        $output .= html_writer::tag('p', 'Work in progress - for now, when accepted turn into theme settings.');

        if (!empty($this->user->userdetails['customfields'])) {
        foreach($this->user->userdetails['customfields'] as $cfield) {
                $output .= html_writer::tag('p', '\''.$cfield['shortname'].'\' / \''.$cfield['name'].'\' is "'.$cfield['value'].'".');
            }
        }

        return $output;
    }

    protected function create_aboutme($tree) {
        global $OUTPUT;

        $aboutme = new category('aboutme', 'About me');

        // Description.
        if (!empty($this->user->userdetails['description'])) {
            $node = new node('aboutme', 'description', get_string('description'), null, null,
                $this->user->userdetails['description']);
            $aboutme->add_node($node);
        }

        // Interests.
        if (!empty($tree->categories['contact']->nodes['interests'])) {
            $node = new node('aboutme', 'interests', get_string('interests'), null, null,
                $tree->categories['contact']->nodes['interests']->content);
            $aboutme->add_node($node);
        }


        return $aboutme;
    }

    protected function create_editprofile() {
        $editprofile = new category('editprofile', 'Edit profile');

        global $CFG, $DB, $PAGE, $SITE, $USER;
        //$returnto = optional_param('returnto', null, PARAM_ALPHA); // Code determining where to return to after save.
        if ($this->user->id !== -1) {
            $usercontext = \context_user::instance($this->user->id);
            $editoroptions = array(
                'maxfiles'   => EDITOR_UNLIMITED_FILES,
                'maxbytes'   => $CFG->maxbytes,
                'trusttext'  => false,
                'forcehttps' => false,
                'context'    => $usercontext
            );
            $this->user = file_prepare_standard_editor($this->user, 'description', $editoroptions, $usercontext, 'user', 'profile', 0);
        } else {
            $usercontext = null;
            // This is a new user, we don't want to add files here.
            $editoroptions = array(
                'maxfiles' => 0,
                'maxbytes' => 0,
                'trusttext' => false,
                'forcehttps' => false,
                'context' => $coursecontext
            );
        }
        // Prepare filemanager draft area.
        $draftitemid = 0;
        $filemanagercontext = $editoroptions['context'];
        $filemanageroptions = array(
            'maxbytes'       => $CFG->maxbytes,
            'subdirs'        => 0,
            'maxfiles'       => 1,
            'accepted_types' => 'web_image');
        \file_prepare_draft_area($draftitemid, $filemanagercontext->id, 'user', 'newicon', 0, $filemanageroptions);
        $this->user->imagefile = $draftitemid;

        // Deciding where to send the user back in most cases.
        //if ($returnto === 'profile') {
            if ($this->course->id != SITEID) {
                $returnurl = new \moodle_url('/user/view.php', array('id' => $this->user->id, 'course' => $course->id));
            } else {
                $returnurl = new \moodle_url('/user/profile.php', array('id' => $this->user->id));
            }
        /*} else {
            $returnurl = new \moodle_url('/user/preferences.php', array('userid' => $user->id));
        }*/

        $editprofileform = new editprofile_form(new \moodle_url($PAGE->url), array(
            'editoroptions' => $editoroptions,
            'filemanageroptions' => $filemanageroptions,
            'user' => $this->user));


        if ($editprofileform->is_cancelled()) {
            redirect($returnurl);
        } else if ($usernew = $editprofileform->get_data()) {
            $usercreated = false;
            if (empty($usernew->auth)) {
                // User editing self.
                $authplugin = get_auth_plugin($user->auth);
                unset($usernew->auth); // Can not change/remove.
            } else {
                $authplugin = get_auth_plugin($usernew->auth);
            }

            $usernew->timemodified = time();
            $createpassword = false;

            if ($usernew->id == -1) {
                unset($usernew->id);
                $createpassword = !empty($usernew->createpassword);
                unset($usernew->createpassword);
                $usernew = file_postupdate_standard_editor($usernew, 'description', $editoroptions, null, 'user', 'profile', null);
                $usernew->mnethostid = $CFG->mnet_localhost_id; // Always local user.
                $usernew->confirmed  = 1;
                $usernew->timecreated = time();
                if ($authplugin->is_internal()) {
                    if ($createpassword or empty($usernew->newpassword)) {
                        $usernew->password = '';
                    } else {
                        $usernew->password = hash_internal_user_password($usernew->newpassword);
                    }
                } else {
                    $usernew->password = AUTH_PASSWORD_NOT_CACHED;
                }
                $usernew->id = user_create_user($usernew, false, false);

                if (!$authplugin->is_internal() and $authplugin->can_change_password() and !empty($usernew->newpassword)) {
                    if (!$authplugin->user_update_password($usernew, $usernew->newpassword)) {
                        // Do not stop here, we need to finish user creation.
                        debugging(get_string('cannotupdatepasswordonextauth', '', '', $usernew->auth), DEBUG_NONE);
                    }
                }
                $usercreated = true;
            } else {
                $usernew = file_postupdate_standard_editor($usernew, 'description', $editoroptions, $usercontext, 'user', 'profile', 0);
                // Pass a true old $user here.
                if (!$authplugin->user_update($this->user, $usernew)) {
                    // Auth update failed.
                    print_error('cannotupdateuseronexauth', '', '', $this->user->auth);
                }
                user_update_user($usernew, false, false);

                // Set new password if specified.
                if (!empty($usernew->newpassword)) {
                    if ($authplugin->can_change_password()) {
                        if (!$authplugin->user_update_password($usernew, $usernew->newpassword)) {
                            print_error('cannotupdatepasswordonextauth', '', '', $usernew->auth);
                        }
                        unset_user_preference('create_password', $usernew); // Prevent cron from generating the password.

                        if (!empty($CFG->passwordchangelogout)) {
                            // We can use SID of other user safely here because they are unique,
                            // the problem here is we do not want to logout admin here when changing own password.
                            \core\session\manager::kill_user_sessions($usernew->id, session_id());
                        }
                        if (!empty($usernew->signoutofotherservices)) {
                            webservice::delete_user_ws_tokens($usernew->id);
                        }
                    }
                }

                // Force logout if user just suspended.
                if (isset($usernew->suspended) and $usernew->suspended and !$this->user->suspended) {
                    \core\session\manager::kill_user_sessions($this->user->id);
                }
            }

            $usercontext = \context_user::instance($usernew->id);

            // Update preferences.
            useredit_update_user_preference($usernew);

            // Update tags.
            if (empty($USER->newadminuser) && isset($usernew->interests)) {
                useredit_update_interests($usernew, $usernew->interests);
            }

            // Update user picture.
            if (empty($USER->newadminuser)) {
                \core_user::update_picture($usernew, $filemanageroptions);
            }

            // Update mail bounces.
            useredit_update_bounces($this->user, $usernew);

            // Update forum track preference.
            useredit_update_trackforums($this->user, $usernew);

            // Save custom profile fields data.
            profile_save_data($usernew);

            // Reload from db.
            $usernew = $DB->get_record('user', array('id' => $usernew->id));

            if ($createpassword) {
                setnew_password_and_mail($usernew);
                unset_user_preference('create_password', $usernew);
                set_user_preference('auth_forcepasswordchange', 1, $usernew);
            }

            // Trigger update/create event, after all fields are stored.
            if ($usercreated) {
                \core\event\user_created::create_from_userid($usernew->id)->trigger();
            } else {
                \core\event\user_updated::create_from_userid($usernew->id)->trigger();
            }

            if ($this->user->id == $USER->id) {
                // Override old $USER session variable.
                foreach ((array)$usernew as $variable => $value) {
                    if ($variable === 'description' or $variable === 'password') {
                        // These are not set for security nad perf reasons.
                        continue;
                    }
                    $USER->$variable = $value;
                }
                // Preload custom fields.
                profile_load_custom_fields($USER);

                if (!empty($USER->newadminuser)) {
                    unset($USER->newadminuser);
                    // Apply defaults again - some of them might depend on admin user info, backup, roles, etc.
                    admin_apply_default_settings(null, false);
                    // Admin account is fully configured - set flag here in case the redirect does not work.
                    unset_config('adminsetuppending');
                    // Redirect to admin/ to continue with installation.
                    //redirect("$CFG->wwwroot/$CFG->admin/");
                } else if (empty($SITE->fullname)) {
                    // Somebody double clicked when editing admin user during install.
                    //redirect("$CFG->wwwroot/$CFG->admin/");
                } else {
                    //redirect($returnurl);
                }
            } else {
                \core\session\manager::gc(); // Remove stale sessions.
                //redirect("$CFG->wwwroot/$CFG->admin/user.php");
            }
            // Never reached..
        }

        $node = new node('editprofile', 'editprofile', '', null, null, $editprofileform->render());
        $editprofile->add_node($node);

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
        $tab->content = $this->render($category);
        $tabdata->tabs[] = $tab;

        foreach ($tabcategories as $categoryname) {
            if (!empty($categories[$categoryname])) {
                $category = $categories[$categoryname];
                $markup = $this->render($category);
                if (!empty($markup)) {
                    $tab = new \stdClass;
                    $tab->name = $category->name;
                    $tab->displayname = $category->title;
                    $tab->content = $markup;
                    $tabdata->tabs[] = $tab;
                }
                unset($categories[$categoryname]);
            }
        }

        // Misc tab.
        $misccontent = html_writer::start_tag('div', array('class' => 'row'));
        foreach ($categories as $categoryname => $category) {
            $misccontent .= html_writer::start_tag('div', array('class' => 'col-12 '.$categoryname));
            $misccontent .= $this->render($category);
            $misccontent .= html_writer::end_tag('div');
        }
        $misccontent .= html_writer::end_tag('div');
        $tab = new \stdClass;
        $tab->name = 'misc';
        $tab->displayname = 'Misc';
        $tab->content = $misccontent;
        $tabdata->tabs[] = $tab;

        // Edit profile tab.
        $category = $this->create_editprofile();
        $tab = new \stdClass;
        $tab->name = $category->name;
        $tab->displayname = $category->title;
        $tab->content = $this->render($category);
        $tabdata->tabs[] = $tab;

        return $this->render_from_template('theme_adaptable/tabs', $tabdata);
    }
}
