<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * moodlewatermark module admin settings and defaults
 *
 * @package    /moodlewatermark/
 * @copyright 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->dirroot.'/mod/moodlewatermark/classes/fileutil.php');

if ($ADMIN->fulltree) {

    $displayoptions = \mod_moodlewatermark\fileutil::get_displayoptions(array(
        \mod_moodlewatermark\fileutil::$DISPLAY_AUTO,
        \mod_moodlewatermark\fileutil::$DISPLAY_EMBED,
        \mod_moodlewatermark\fileutil::$DISPLAY_FRAME,
        \mod_moodlewatermark\fileutil::$DISPLAY_DOWNLOAD,
        \mod_moodlewatermark\fileutil::$DISPLAY_OPEN,
        \mod_moodlewatermark\fileutil::$DISPLAY_NEW,
        \mod_moodlewatermark\fileutil::$DISPLAY_POPUP,
    ));
    $defaultdisplayoptions = array(
        \mod_moodlewatermark\fileutil::$DISPLAY_AUTO,
        \mod_moodlewatermark\fileutil::$DISPLAY_EMBED,
        \mod_moodlewatermark\fileutil::$DISPLAY_DOWNLOAD,
        \mod_moodlewatermark\fileutil::$DISPLAY_OPEN,
        \mod_moodlewatermark\fileutil::$DISPLAY_POPUP,
    );

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('moodlewatermark/framesize',
        get_string('framesize', 'moodlewatermark'), get_string('configframesize', 'moodlewatermark'), 130, PARAM_INT));
    $settings->add(new admin_setting_configmultiselect('moodlewatermark/displayoptions',
        get_string('displayoptions', 'moodlewatermark'), get_string('configdisplayoptions', 'moodlewatermark'),
        $defaultdisplayoptions, $displayoptions));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('moodlewatermarkmodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox('moodlewatermark/printintro',
        get_string('printintro', 'moodlewatermark'), get_string('printintroexplain', 'moodlewatermark'), 1));
    $settings->add(new admin_setting_configselect('moodlewatermark/display',
        get_string('displayselect', 'moodlewatermark'), get_string('displayselectexplain', 'moodlewatermark'),\mod_moodlewatermark\fileutil::$DISPLAY_AUTO,
        $displayoptions));
    $settings->add(new admin_setting_configcheckbox('moodlewatermark/showsize',
        get_string('showsize', 'moodlewatermark'), get_string('showsize_desc', 'moodlewatermark'), 0));
    $settings->add(new admin_setting_configcheckbox('moodlewatermark/showtype',
        get_string('showtype', 'moodlewatermark'), get_string('showtype_desc', 'moodlewatermark'), 0));
    $settings->add(new admin_setting_configcheckbox('moodlewatermark/showdate',
        get_string('showdate', 'moodlewatermark'), get_string('showdate_desc', 'moodlewatermark'), 0));
    $settings->add(new admin_setting_configtext('moodlewatermark/popupwidth',
        get_string('popupwidth', 'moodlewatermark'), get_string('popupwidthexplain', 'moodlewatermark'), 620, PARAM_INT, 7));
    $settings->add(new admin_setting_configtext('moodlewatermark/popupheight',
        get_string('popupheight', 'moodlewatermark'), get_string('popupheightexplain', 'moodlewatermark'), 450, PARAM_INT, 7));
}
