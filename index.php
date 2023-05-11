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
 * List of all resources with watermark in course
 *
 * @package    mod_moodlewatermark
 * @copyright
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT); //id do curso

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
//o utilizador necessita de ter acesso ao curso para aceder.
require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_moodlewatermark\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

//Dados do módulo
$strmoodlewatermark     = get_string('modulename', 'moodlewatermark');
$strmoodlewatermarks    = get_string('modulenameplural', 'moodlewatermark');
$strsectionname  = get_string('sectionname', 'format_'.$course->format);
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/moodlewatermark/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$strmoodlewatermarks);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strmoodlewatermarks);
echo $OUTPUT->header();
echo $OUTPUT->heading($strmoodlewatermarks);

if (!$moodlewatermarks = get_all_instances_in_course('moodlewatermark', $course)) {
    notice(get_string('thereareno', 'moodle', $strmoodlewatermarks), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $table->head  = array ($strsectionname, $strname, $strintro);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strlastmodified, $strname, $strintro);
    $table->align = array ('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($moodlewatermarks as $moodlewatermark) {
    $cm = $modinfo->cms[$moodlewatermark->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($moodlewatermark->section !== $currentsection) {
            if ($moodlewatermark->section) {
                $printsection = get_section_name($course, $moodlewatermark->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $moodlewatermark->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($moodlewatermark->timemodified)."</span>";
    }

    $extra = empty($cm->extra) ? '' : $cm->extra;

    $class = $moodlewatermark->visible ? '' : 'class="dimmed"'; // hidden modules are dimmed
    $table->data[] = array (
        $printsection,
        "<a $class $extra href=\"view.php?id=$cm->id\">".format_string($moodlewatermark->name)."</a>",
        format_module_intro('moodlewatermark', $moodlewatermark, $cm->id));
}

echo html_writer::table($table);

echo $OUTPUT->footer();
