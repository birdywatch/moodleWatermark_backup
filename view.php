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

//Demonstra o ficheiro

/**
 * Activity view
 *
 * @package    mod_moodlewatermark
 * @copyright
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_once($CFG->dirroot.'/mod/moodlewatermark/lib.php');
require_once($CFG->dirroot.'/mod/moodlewatermark/locallib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/mod/moodlewatermark/classes/fileutil.php');

//Parametros opcionais
$id       = optional_param('id', 0, PARAM_INT); //ID do modulo do curso
$r        = optional_param('r', 0, PARAM_INT);  //Identificação do ficheiro
$redirect = optional_param('redirect', 0, PARAM_BOOL);
$forceview = optional_param('forceview', 0, PARAM_BOOL);


if ($r) {
    if (!$moodlewatermark = $DB->get_record('moodlewatermark', array('id'=>$r))) {
        print_error('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('moodlewatermark', $moodlewatermark->id, $moodlewatermark->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('moodlewatermark', $id)) {
        print_error('invalidcoursemodule');
    }
    $moodlewatermark = $DB->get_record('moodlewatermark', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/moodlewatermark:view', $context);


moodlewatermark_view($moodlewatermark, $course, $cm, $context);

// Define o url que vai ser demonstrado com o ficheiro
$PAGE->set_url('/mod/moodlewatermark/view.php', array('id' => $cm->id));

// Requisita os ficheiros ao Moodle API
$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_moodlewatermark', 'content', 0, 'sortorder DESC, id ASC', false);

if (count($files) < 1) {
    moodlewatermark_print_filenotfound($moodlewatermark, $cm, $course);
    die;
} else {
    $file = reset($files);
    unset($files);
}

$moodlewatermark->mainfile = $file->get_filename();
$displaytype = moodlewatermark_get_final_display_type($moodlewatermark);
if ($displaytype ==\mod_moodlewatermark\fileutil::$DISPLAY_OPEN || $displaytype ==\mod_moodlewatermark\fileutil::$DISPLAY_DOWNLOAD) {
    $redirect = true;
}

if ($redirect && !course_get_format($course)->has_view_page() &&
        (has_capability('moodle/course:manageactivities', $context) ||
        has_capability('moodle/course:update', context_course::instance($course->id)))) {
    $redirect = false;
}

if ($redirect && !$forceview) {
    $path = '/'.$context->id.'/mod_moodlewatermark/content/'.$moodlewatermark->revision.$file->get_filepath().$file->get_filename();

    $fullurl = moodle_url::make_file_url('/pluginfile.php', $path, $displaytype ==\mod_moodlewatermark\fileutil::$DISPLAY_DOWNLOAD);

    redirect($fullurl);
}

//Apresenta o ficheiro com o formato escolhido.
switch ($displaytype) {
    case \mod_moodlewatermark\fileutil::$DISPLAY_EMBED:
        moodlewatermark_display_embed($moodlewatermark, $cm, $course, $file);
        break;
    case \mod_moodlewatermark\fileutil::$DISPLAY_FRAME:
        moodlewatermark_display_frame($moodlewatermark, $cm, $course, $file);
        break;
    default:
        moodlewatermark_print_workaround($moodlewatermark, $cm, $course, $file);
        break;
}

