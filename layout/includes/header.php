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
 * @package   theme_adaptable
 * @copyright 2015-2019 Jeremy Hopkins (Coventry University)
 * @copyright 2015-2019 Fernando Acedo (3-bits.com)
 * @copyright 2017-2019 Manoj Solanki (Coventry University)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

// Set HTTPS if needed.
if (empty($CFG->loginhttps)) {
    $wwwroot = $CFG->wwwroot;
} else {
    $wwwroot = str_replace("http://", "https://", $CFG->wwwroot);
}

// Check if this is a course or module page and check setting to hide site title.
// If not one of these pages, by default show it (set $hidesitetitle to false).
if ( (strstr($PAGE->pagetype, 'course')) ||
     (strstr($PAGE->pagetype, 'mod')) && ($this->page->course->id > 1) ) {
    $hidesitetitle = !empty(($PAGE->theme->settings->coursepageheaderhidesitetitle)) ? true : false;
} else {
    $hidesitetitle = false;
}

// Screen size.
theme_adaptable_initialise_zoom();
$setzoom = theme_adaptable_get_zoom();

theme_adaptable_initialise_full();
$setfull = theme_adaptable_get_full();

$bsoptionsdata = array('data' => array());

// Main navbar.
if (isset($PAGE->theme->settings->stickynavbar) && $PAGE->theme->settings->stickynavbar == 1
    && $PAGE->pagetype != "grade-report-grader-index" && $PAGE->bodyid != "page-grade-report-grader-index") {
    $fixedheader = true;
    $bsoptionsdata['data']['stickynavbar'] = true;
} else {
    $bsoptionsdata['data']['stickynavbar'] = false;
}

// JS calls.
$PAGE->requires->js_call_amd('theme_adaptable/adaptable', 'init');
$PAGE->requires->js_call_amd('theme_adaptable/bsoptions', 'init', $bsoptionsdata);
$PAGE->requires->js_call_amd('theme_adaptable/drawer', 'init');

// Layout.
$left = (!right_to_left());  // To know if to add 'pull-right' and 'desktop-first-column' classes in the layout for LTR.

// Navbar Menu.
$shownavbar = false;
if (
    (isloggedin() && !isguestuser()) ||
    (!empty($PAGE->theme->settings->enablenavbarwhenloggedout)) ) {

    // Show navbar unless disabled by config.
    if (empty($PAGE->layout_options['nonavbar'])) {
        $shownavbar = true;
    }
}
// Load header background image if it exists.
$headerbg = '';
if (!empty($PAGE->theme->settings->categoryhavecustomheader)) {
    $currenttopcat = \theme_adaptable\toolbox::get_current_top_level_catetgory();
    if (!empty($currenttopcat)) {
        $categoryheaderbgimageset = 'categoryheaderbgimage'.$currenttopcat;
        if (!empty($PAGE->theme->settings->$categoryheaderbgimageset)) {
            $headerbg = ' class="headerbgimage" style="background-image: ' .
            ' url('.$PAGE->theme->setting_file_url($categoryheaderbgimageset, $categoryheaderbgimageset).');"';
        }
    }
} else {
    $currenttopcat = false;
}
if ((empty($headerbg)) && (!empty($PAGE->theme->settings->headerbgimage))) {
    $headerbg = ' class="headerbgimage" style="background-image: ' .
    ' url('.$PAGE->theme->setting_file_url('headerbgimage', 'headerbgimage').');"';
}

// Choose the header style.  There styles available are:
// "style1"  (original header)
// "style2"  (2 row header).

if (!empty($PAGE->theme->settings->headerstyle)) {
    $adaptableheaderstyle = $PAGE->theme->settings->headerstyle;
} else {
    $adaptableheaderstyle = "style1";
}

// User image, name in user menu dropdown.
$userpic = '';
$username = '';
$usermenu = '';
// Only used when user is logged in.
if (isloggedin()) {
    // User icon.
    $userpic = $OUTPUT->user_picture($USER, array('link' => false, 'visibletoscreenreaders' => false,
               'size' => 50, 'class' => 'userpicture'));
    // User name.
    $username = format_string(fullname($USER));

    // User menu dropdown.
    if (!empty($PAGE->theme->settings->usernameposition)) {
        $usernameposition = $PAGE->theme->settings->usernameposition;
        if ($usernameposition == 'right') {
            $usernamepositionleft = false;
        } else {
            $usernamepositionleft = true;
        }
    } else {
        $usernamepositionleft = true;
    }

    // Set template data.
    $data = [
        'username' => $username,
        'userpic' => $userpic,
        'showusername' => $PAGE->theme->settings->showusername,
        'usernamepositionleft' => $usernamepositionleft,
        'userprofilemenu' => $OUTPUT->user_profile_menu(),
    ];
    $usermenu = $OUTPUT->render_from_template('theme_adaptable/usermenu', $data);
}

// Social icons class.
$showicons = "";
$showicons = $PAGE->theme->settings->blockicons;
if ($showicons == 1) {
    $showiconsclass = "showblockicons";
} else {
    $showiconsclass = " ";
}

$standardscreenwidthclass = 'standard';
if (!empty($PAGE->theme->settings->standardscreenwidth)) {
    $standardscreenwidthclass = $PAGE->theme->settings->standardscreenwidth;
}

// HTML header.
echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="icon" href="<?php echo $OUTPUT->favicon(); ?>" />

<?php
// Include header.
require_once(dirname(__FILE__) . '/head.php');

// If it is a mobile and the header is not hidden or it is a desktop there will be a page header.
$pageheader = 'has-page-header';

$hasheaderbg = '';
if (!empty($headerbg)) {
    $hasheaderbg = 'has-header-bg';
}

$nomobilenavigation = '';
if (!empty($PAGE->theme->settings->responsivesectionnav)) {
    $nomobilenavigation = 'nomobilenavigation';
}
?>
<body <?php echo $OUTPUT->body_attributes(array('theme_adaptable', 'two-column', $setzoom, 'header-'.$adaptableheaderstyle,
        $pageheader, $hasheaderbg, $nomobilenavigation)); ?>>

<?php
echo $OUTPUT->standard_top_of_body_html();

// Development or wrong moodle version alert.
// echo $OUTPUT->get_dev_alert();.
?>

<div id="page" class="<?php echo "$setfull $showiconsclass $standardscreenwidthclass"; ?>">

<?php
    echo $OUTPUT->get_alert_messages();

$headercontext = [
    'output' => $OUTPUT
];

if ((!isloggedin() || isguestuser()) && ($PAGE->pagetype != "login-index")) {
    if ($PAGE->theme->settings->displaylogin != 'no') {
        $loginformcontext = [
            'displayloginbox' => ($PAGE->theme->settings->displaylogin == 'box') ? true : false,
            'output' => $OUTPUT,
            'token' => s(\core\session\manager::get_login_token()),
            'url' => new moodle_url('/login/index.php')
        ];

        $headercontext['loginoruser'] = $OUTPUT->render_from_template('theme_adaptable/headerloginform', $loginformcontext);
    } else {
        $headercontext['loginoruser'] = '';
    }
} else {
    // Display user profile menu.
    $headercontext['loginoruser'] = '<li class="nav-item dropdown ml-3 ml-md-4 mr-2 mr-md-0">'.$usermenu.'</li>';
}

if (!$hidesitetitle) {
    $headercontext['sitetitle'] = $OUTPUT->get_logo_title($currenttopcat);
}

$headercontext['headerbg'] = $headerbg;
$headercontext['shownavbar'] = $shownavbar;
$headercontext['responsivesearchicon'] = (!empty($PAGE->theme->settings->responsivesearchicon)) ? ' d-xs-block d-sm-block d-md-none my-auto' : ' d-none';

if ($adaptableheaderstyle == "style1") {
    $headercontext['menuslinkright'] = (!empty($PAGE->theme->settings->menuslinkright));
    $headercontext['coursesearch'] = new moodle_url('/course/search.php');
    $headercontext['nonavbar'] = (!empty($PAGE->layout_options['nonavbar']));
    $headercontext['langmenu'] = (empty($PAGE->layout_options['langmenu']) || $PAGE->layout_options['langmenu']);
    $headercontext['responsiveheader'] = $PAGE->theme->settings->responsiveheader;

    if (!$headercontext['nonavbar']) {
        // Social icons.
        if ($PAGE->theme->settings->socialorsearch == 'social') {
            $headercontext['socialorsearch'] = '<div class="socialbox pull-right '.$PAGE->theme->settings->responsivesocial.'">';
            $headercontext['socialorsearch'] .= $OUTPUT->socialicons().'</div>';
        }
        // Search box.
        if ((!$hidesitetitle) && ($PAGE->theme->settings->socialorsearch == 'search') ) {
            $headercontext['socialorsearch'] = '<div class="searchbox d-none d-lg-block">';
            $headercontext['socialorsearch'] .= '<form action="'.new moodle_url('/course/search.php').'">';
            $headercontext['socialorsearch'] .= '<label class="hidden" for="search-1" style="display: none;">'.get_string("searchcourses").'</label>';
            $headercontext['socialorsearch'] .= '<div class="search-box grey-box bg-white clear-fix">';
            $headercontext['socialorsearch'] .= '<input placeholder="'.get_string("searchcourses", "theme_adaptable").'"accesskey="6" ';
            $headercontext['socialorsearch'] .= 'class="search_tour bg-white no-border left search-box__input ui-autocomplete-input" ';
            $headercontext['socialorsearch'] .= 'type="text" name="search" id="search-1" autocomplete="off">';
            $headercontext['socialorsearch'] .= '<button title="'.get_string("searchcourses", "theme_adaptable").'" type="submit" class="no-border bg-white pas search-box__button">';
            $headercontext['socialorsearch'] .= '<abbr class="fa fa-search" title="'.get_string("searchcourses", "theme_adaptable").'"></abbr>';
            $headercontext['socialorsearch'] .= '</button></div></form></div>';
        }
    }

    echo $OUTPUT->render_from_template('theme_adaptable/headerstyleone', $headercontext);
} else if ($adaptableheaderstyle == "style2") {
    $headercontext['topmenus'] = $OUTPUT->get_top_menus(false);
    if (empty($PAGE->layout_options['langmenu']) || $PAGE->layout_options['langmenu']) {
        $headercontext['langmenu'] = '<div class="my-auto">'.$OUTPUT->lang_menu(false).'</div>';
    }

    echo $OUTPUT->render_from_template('theme_adaptable/headerstyletwo', $headercontext);
}

// Navbar Menu.
if ($shownavbar) {
?>

<div id="nav-drawer" data-region="drawer" class="d-print-none moodle-has-zindex closed" aria-hidden="true" tabindex="-1">
    <div id="nav-drawer-inner">
        <nav class="list-group">
            <ul class="list-unstyled components">

                <?php
                echo $OUTPUT->navigation_menu('main-navigation-drawer');

                if (empty($PAGE->theme->settings->disablecustommenu)) {
                    echo $OUTPUT->custom_menu_drawer();
                }
                if ($PAGE->theme->settings->enabletoolsmenus) {
                    echo $OUTPUT->tools_menu('tools-menu-drawer');
                }
                ?>

            </ul>
        </nav>

        <nav class="list-group m-t-1">
            <?php echo $OUTPUT->context_mobile_settings_menu(); ?>
            <a class="list-group-item list-group-item-action " href="<?php echo $CFG->wwwroot.'/admin/search.php'; ?>">
                <div class="m-l-0">
                    <div class="media">
                        <span class="media-left">
                            <i class="icon fa fa-wrench fa-fw" aria-hidden="true"></i>
                        </span>
                        <span class="media-body "><?php echo get_string('administrationsite'); ?></span>
                    </div>
                </div>
            </a>
        </nav>
    </div>
</div>

<div id="main-navbar" class="d-none d-lg-block">
    <div class="container">
        <div class="navbar navbar-expand-md btco-hover-menu">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">

            <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">

            <nav role="navigation" aria-label="<?php echo get_string('sitelinkslabel', 'theme_adaptable'); ?>">
                <ul class="navbar-nav">
                    <?php echo $OUTPUT->navigation_menu('main-navigation'); ?>

                    <?php
                    if (empty($PAGE->theme->settings->disablecustommenu)) {
                        echo $OUTPUT->custom_menu();
                    }
                    if ($PAGE->theme->settings->enabletoolsmenus) {
                        echo $OUTPUT->tools_menu();
                    }
                    ?>
                </ul>
            </nav>

                <ul class="navbar-nav ml-auto">

                    <?php
                    $navbareditsettings = $PAGE->theme->settings->editsettingsbutton;
                    $showcog = true;
                    $showeditbuttons = false;

                    if ($navbareditsettings == 'button') {
                        $showeditbuttons = true;
                        $showcog = false;
                    } else if ($navbareditsettings == 'cogandbutton') {
                        $showeditbuttons = true;
                    }

                    $coursemenucontent = $OUTPUT->context_header_settings_menu();
                    if ($showcog) {
                        if ($coursemenucontent) {
                    ?>
                            <li class="nav-item mr-2">
                                <div class="context-header-settings-menu">
                                    <?php echo $coursemenucontent; ?>
                                </div>
                            </li>
                    <?php
                        }
                    }

                    $othermenucontent = $OUTPUT->region_main_settings_menu();
                    if ($showcog) {
                        if ($othermenucontent) {
                    ?>
                            <li class="nav-item mr-2">
                                <div id="region-main-settings-menu" class="region-main-settings-menu">
                                    <?php echo $othermenucontent; ?>
                                </div>
                            </li>
                    <?php
                        }
                    }
                    ?>

                    <?php
                    // Ensure to only hide the button on relevant pages.  Some pages will need the button, such as the
                    // dashboard page. Checking if the cog is being displayed above to figure out if it still needs to
                    // show (when there is no cog). Also show mod pages (e.g. Forum, Lesson) as these sometimes have
                    // a button for a specific purpose.
                    if ( ($showeditbuttons) || (
                        (empty($coursemenucontent)) &&
                        (empty($othermenucontent))
                        ) ||
                        (strstr($PAGE->pagetype, 'mod-'))
                    ) {
                        $pageheadingbutton = $OUTPUT->page_heading_button();
                        if (!empty($pageheadingbutton)) {
                        ?>
                        <li class="nav-item mx-0 my-auto">
                             <div id="edittingbutton">
                                <?php echo $pageheadingbutton; ?>
                            </div>
                        </li>
                        <?php
                        }
                    }
                    ?>

                    <?php
                    if (isloggedin()) {
                        if (!empty($this->page->theme->settings->enableshowhideblocks)) {
                            $zoomside = ((!empty($this->page->theme->settings->blockside)) &&
                                        ($this->page->theme->settings->blockside == 1)) ? 'left' : 'right';
                            $hidetitle = get_string('hideblocks', 'theme_adaptable');
                            $showtitle = get_string('showblocks', 'theme_adaptable');
                            if ($setzoom == 'zoomin') { // Blocks not shown.
                                $zoominicontitle = $showtitle;
                                if ($zoomside == 'right') {
                                    $icontype = 'outdent';
                                } else {
                                    $icontype = 'indent';
                                }
                            } else {
                                $zoominicontitle = $hidetitle;
                                if ($zoomside == 'right') {
                                    $icontype = 'indent';
                                } else {
                                    $icontype = 'outdent';
                                }
                            }
                            echo html_writer::start_tag('li', array('class' => 'nav-item mr-1'));
                            echo html_writer::start_tag('div',
                                array('id' => 'zoominicon', 'class' => $zoomside. ' nav-link', 'title' => $zoominicontitle,
                                    'data-hidetitle' => $hidetitle, 'data-showtitle' => $showtitle));
                            echo html_writer::tag('i', '', array('class' => 'fa fa-lg fa-'.$icontype, 'aria-hidden' => 'true'));
                            if ($PAGE->theme->settings->enableshowhideblockstext) {
                                echo html_writer::tag('span', $zoominicontitle, array('class' => 'showhideblocksdesc'));
                            }
                            echo html_writer::end_tag('div');
                            echo html_writer::end_tag('li');
                            $PAGE->requires->js_call_amd('theme_adaptable/zoomin', 'init');
                        }
                        if ($PAGE->theme->settings->enablezoom) { ?>
                            <li class="nav-item mx-0 hbll">
                                <a class="nav-link moodlewidth" href="javascript:void(0);"
                                    title="<?php echo get_string('fullscreen', 'theme_adaptable') ?>">
                                    <i class="fa fa-expand fa-lg"></i>
                                    <?php if ($PAGE->theme->settings->enablezoomshowtext) { ?>
                                    <span class="zoomdesc"><?php echo get_string('fullscreen', 'theme_adaptable') ?></span>
                                    <?php } ?>
                                </a>
                            </li>
                            <li class="nav-item mx-0 sbll">
                                <a class="nav-link moodlewidth" href="javascript:void(0);"
                                    title="<?php echo get_string('standardview', 'theme_adaptable') ?>">
                                    <i class="fa fa-compress fa-lg"></i>
                                    <?php if ($PAGE->theme->settings->enablezoomshowtext) { ?>
                                    <span class="zoomdesc"><?php echo get_string('standardview', 'theme_adaptable') ?></span>
                                    <?php } ?>
                                </a>
                            </li>
                    <?php
                        }
                    }
                    ?>
                </ul>

            </div>
        </div>
    </div>
</div>

<?php
}
?>

</header>

<?php

// Display News Ticker.
echo $OUTPUT->get_news_ticker();
