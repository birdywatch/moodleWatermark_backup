2
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
 * Private Filewithwatermark module utility functions
 *
 * @package    mod_moodleWatermark
 * @copyright
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/filewithwatermark/lib.php");
require_once($CFG->dirroot . '/mod/filewithwatermark/classes/fileutil.php');
require_once($CFG->dirroot . '/mod/filewithwatermark/classes/pdfextractor.php');
require_once($CFG->dirroot . '/mod/filewithwatermark/vendor/autoload.php');

/**
 * Se forem submetidos multiplos ficheiros, define o ficheiro principal
 * Torna o contéudo dos ficheiros não selecionavel, atráves da função save_gs()
 * @param $data
 */
function filewithwatermark_set_mainfile($data)
{
    global $DB, $USER;
    $fs = get_file_storage();
    $cmid = $data->coursemodule;
    $draftitemid = $data->files;
    $context = context_module::instance($cmid);
    if ($draftitemid) {
        $options = array('subdirs' => true, 'embed' => false);
        if ($data->display == \mod_filewithwatermark\fileutil::$DISPLAY_EMBED) {
            $options['embed'] = true;
        }
        file_save_draft_area_files($draftitemid, $context->id, 'mod_filewithwatermark', 'content', 0, $options);
    }
    $files = $fs->get_area_files($context->id, 'mod_filewithwatermark', 'content', 0, 'sortorder', false);

    foreach ($files as $file) {
        $contents = $file->get_content();
        $fs = get_file_storage();
        $filename = md5(uniqid(mt_rand(), true)) . '.pdf';
        $handle = fopen($filename, 'w');
        fwrite($handle, $contents);
        fclose($handle);
        $contextid = $context->id;
        $converted = md5(uniqid(mt_rand(), true)) . '.pdf';
        save_gs($filename, $converted);
        $contents = file_get_contents($converted);
        $file_info = array(
            'contextid' => $file->get_contextid(),

            'component' => $file->get_component(),

            'filearea' => $file->get_filearea(),

            'itemid' => $file->get_itemid(),

            'filepath' => $file->get_filepath(),

            'filename' => $file->get_filename(),

            'timecreated' => $file->get_timecreated(),

            'timemodified' => time(),

            'usermodified' => $USER->id,

        );
        $file->delete();
        $newFile = $fs->create_file_from_string($file_info, $contents);
        $path = '/var/www/moodle/course/';
        unlink($filename);
    }
    if (count($files) == 1) {
        // only one file attached, set it as main file automatically
        $file = reset($files);
        file_set_sortorder($context->id, 'mod_filewithwatermark', 'content', 0, $file->get_filepath(), $file->get_filename(), 1);
    }

}

/**
 * Recebe o caminho do ficheiro origem e o caminho onde se pretende armazenar o ficheiro resultante, e atráves do ghostscript, corre um comando que converte as páginas do pdf para png e converte
 * as páginas de volta a pdf e remove as imagens .png criadas.
 */
function save_gs($input, $output)
{
    global $CFG;
    $filepath = '/var/www/moodle/course/';
    $gsPath = '/usr/bin/gs';

    $inputPath = $filepath . $input;
    $outputPath = $filepath . $output;

    $command = "$gsPath -sDEVICE=png16m -o " . $inputPath . "_%03d.png -r200 -dNOPAUSE -dBATCH -dSAFER -dDownScaleFactor=1 " . $inputPath . "  && convert " . $inputPath . "_*.png " . $outputPath . "  && rm " . $inputPath . "_*.png";
    exec($command, $output, $returnCode);
}
/**
 * Imprime mensagem de erro caso o ficheiro não seja encontrado.
 */
function filewithwatermark_print_filenotfound($filewithwatermark, $cm, $course)
{
    global $DB, $OUTPUT;

    filewithwatermark_print_header($filewithwatermark, $cm, $course);
    filewithwatermark_print_heading($filewithwatermark, $cm, $course);
    filewithwatermark_print_intro($filewithwatermark, $cm, $course);

    echo $OUTPUT->notification(get_string('filenotfound', 'filewithwatermark'));

    echo $OUTPUT->footer();
    die;
}

/**
 * Imprime a introdução da instancia do módulo
 */
function filewithwatermark_print_intro($filewithwatermark, $cm, $course, $ignoresettings = false)
{
    global $OUTPUT;

    $options = empty($filewithwatermark->displayoptions) ? array() : unserialize($filewithwatermark->displayoptions);

    $extraintro = filewithwatermark_get_optional_details($filewithwatermark, $cm);
    if ($extraintro) {
        $extraintro = html_writer::tag('p', $extraintro, array('class' => 'resourcedetails'));
    }

    if ($ignoresettings || !empty($options['printintro']) || $extraintro) {
        $gotintro = trim(strip_tags($filewithwatermark->intro));
        if ($gotintro || $extraintro) {
            echo $OUTPUT->box_start('mod_introbox', 'resourceintro');
            if ($gotintro) {
                echo format_module_intro('filewithwatermark', $filewithwatermark, $cm->id);
            }
            echo $extraintro;
            echo $OUTPUT->box_end();
        }
    }
}

/**
 * Imprime o header da instancia do módulo
 *
 * @param object $filewithwatermark
 * @param object $cm
 * @param object $course
 * @return void
 */
function filewithwatermark_print_header($filewithwatermark, $cm, $course)
{
    global $PAGE, $OUTPUT;

    $PAGE->set_title($course->shortname . ': ' . $filewithwatermark->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($filewithwatermark);
    echo $OUTPUT->header();
}

/**
 * Imprime o titulo da instancia do módulo
 *
 */
function filewithwatermark_print_heading($filewithwatermark, $cm, $course, $notused = false)
{
    global $OUTPUT;
    echo $OUTPUT->heading(format_string($filewithwatermark->name), 2);
}

/**
 * Decide o melhor formato de apresentação
 */
function filewithwatermark_get_final_display_type($filewithwatermark)
{
    global $CFG, $PAGE;

    if ($filewithwatermark->display != \mod_filewithwatermark\fileutil::$DISPLAY_AUTO) {
        return $filewithwatermark->display;
    }

    if (empty($filewithwatermark->mainfile)) {
        return \mod_filewithwatermark\fileutil::$DISPLAY_DOWNLOAD;
    } else {
        $mimetype = mimeinfo('type', $filewithwatermark->mainfile);
    }

    if (file_mimetype_in_typegroup($mimetype, 'archive')) {
        return \mod_filewithwatermark\fileutil::$DISPLAY_DOWNLOAD;
    }
    if (file_mimetype_in_typegroup($mimetype, array('web_image', '.htm', 'web_video', 'web_audio'))) {
        return \mod_filewithwatermark\fileutil::$DISPLAY_EMBED;
    }

    return \mod_filewithwatermark\fileutil::$DISPLAY_OPEN;
}

/**
 * Apresenta o ficheiro embutido
 */
function filewithwatermark_display_embed($filewithwatermark, $cm, $course, $file)
{
    global $CFG, $PAGE, $OUTPUT;

    $clicktoopen = filewithwatermark_get_clicktoopen($file, $filewithwatermark->revision);

    $context = context_module::instance($cm->id);
    $moodleurl = moodle_url::make_pluginfile_url(
        $context->id,
        'mod_filewithwatermark',
        'content', $filewithwatermark->revision,
        $file->get_filepath(), $file->get_filename()
    );

    $title = $filewithwatermark->name;

    $code = resourcelib_embed_pdf($moodleurl->out(), $title, $clicktoopen);

    filewithwatermark_print_header($filewithwatermark, $cm, $course);
    filewithwatermark_print_heading($filewithwatermark, $cm, $course);

    echo $code;

    filewithwatermark_print_intro($filewithwatermark, $cm, $course);

    echo $OUTPUT->footer();
    die;
}


/**
 *Apresenta a moldura do ficheiro
 *
 */
function filewithwatermark_display_frame($filewithwatermark, $cm, $course, $file)
{
    global $PAGE, $OUTPUT, $CFG;

    $frame = optional_param('frameset', 'main', PARAM_ALPHA);

    if ($frame === 'top') {
        $PAGE->set_pagelayout('frametop');
        filewithwatermark_print_header($filewithwatermark, $cm, $course);
        filewithwatermark_print_heading($filewithwatermark, $cm, $course);
        filewithwatermark_print_intro($filewithwatermark, $cm, $course);
        echo $OUTPUT->footer();
        die;

    } else {
        $config = get_config('filewithwatermark');
        $context = context_module::instance($cm->id);
        $path = '/' . $context->id . '/mod_filewithwatermark/content/' . $filewithwatermark->revision . $file->get_filepath() . $file->get_filename();
        $fileurl = file_encode_url($CFG->wwwroot . '/pluginfile.php', $path, false);
        $navurl = "$CFG->wwwroot/mod/filewithwatermark/view.php?id=$cm->id&amp;frameset=top";
        $title = strip_tags(format_string($course->shortname . ': ' . $filewithwatermark->name));
        $framesize = $config->framesize;
        $contentframetitle = s(format_string($filewithwatermark->name));
        $modulename = s(get_string('modulename', 'filewithwatermark'));
        $dir = get_string('thisdirection', 'langconfig');

        $file = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html dir="$dir">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>$title</title>
  </head>
  <frameset rows="$framesize,*">
    <frame src="$navurl" title="$modulename" />
    <frame src="$fileurl" title="$contentframetitle" />
  </frameset>
</html>
EOF;

        @header('Content-Type: text/html; charset=utf-8');
        echo $file;
        die;
    }
}

/**
 * Obtém detalhes adicionais da instância, caso estejam definidos nas definições da instancia
 */
function filewithwatermark_get_optional_details($filewithwatermark, $cm)
{
    global $DB;

    $details = '';

    $options = empty($filewithwatermark->displayoptions) ? array() : @unserialize($filewithwatermark->displayoptions);
    if (!empty($options['showsize']) || !empty($options['showtype']) || !empty($options['showdate'])) {
        if (!array_key_exists('filedetails', $options)) {
            $filedetails = filewithwatermark_get_file_details($filewithwatermark, $cm);
        } else {
            $filedetails = $options['filedetails'];
        }
        $size = '';
        $type = '';
        $date = '';
        $langstring = '';
        $infodisplayed = 0;
        if (!empty($options['showsize'])) {
            if (!empty($filedetails['size'])) {
                $size = display_size($filedetails['size']);
                $langstring .= 'size';
                $infodisplayed += 1;
            }
        }
        if (!empty($options['showtype'])) {
            if (!empty($filedetails['type'])) {
                $type = $filedetails['type'];
                $langstring .= 'type';
                $infodisplayed += 1;
            }
        }
        if (!empty($options['showdate']) && (!empty($filedetails['modifieddate']) || !empty($filedetails['uploadeddate']))) {
            if (!empty($filedetails['modifieddate'])) {
                $date = get_string('modifieddate', 'mod_filewithwatermark', userdate(
                    $filedetails['modifieddate'],
                    get_string('strftimedatetimeshort', 'langconfig')
                )
                );
            } else if (!empty($filedetails['uploadeddate'])) {
                $date = get_string('uploadeddate', 'mod_filewithwatermark', userdate(
                    $filedetails['uploadeddate'],
                    get_string('strftimedatetimeshort', 'langconfig')
                )
                );
            }
            $langstring .= 'date';
            $infodisplayed += 1;
        }

        if ($infodisplayed > 1) {
            $details = get_string(
                "filewithwatermarkdetails_{$langstring}",
                'filewithwatermark',
                (object) array('size' => $size, 'type' => $type, 'date' => $date)
            );
        } else {
            $details = $size . $type . $date;
        }
    }

    return $details;
}

/**
 * Cria um link que o utilizador tem de premir para abrir o ficheiro
 */
function filewithwatermark_get_clicktoopen($file, $revision, $extra = '')
{
    global $CFG;

    $filename = $file->get_filename();
    $path = '/' . $file->get_contextid() . '/mod_filewithwatermark/content/' . $revision . $file->get_filepath() . $file->get_filename();
    $fullurl = file_encode_url($CFG->wwwroot . '/pluginfile.php', $path, false);

    $string = get_string('clicktoopen2', 'filewithwatermark', "<a href=\"$fullurl\" $extra>$filename</a>");

    return $string;
}

/**
 * Imprime a informação da instância caso JS esteja indisponivel
 */
function filewithwatermark_print_workaround($filewithwatermark, $cm, $course, $file)
{
    global $CFG, $OUTPUT;

    filewithwatermark_print_header($filewithwatermark, $cm, $course);
    filewithwatermark_print_heading($filewithwatermark, $cm, $course, true);
    filewithwatermark_print_intro($filewithwatermark, $cm, $course, true);

    $filewithwatermark->mainfile = $file->get_filename();
    echo '<div class="resourceworkaround">';
    switch (filewithwatermark_get_final_display_type($filewithwatermark)) {
        case \mod_filewithwatermark\fileutil::$DISPLAY_POPUP:
            $path = '/' . $file->get_contextid() . '/mod_filewithwatermark/content/' . $filewithwatermark->revision . $file->get_filepath() . $file->get_filename();
            $fullurl = file_encode_url($CFG->wwwroot . '/pluginfile.php', $path, false);
            $options = empty($resource->displayoptions) ? array() : unserialize($filewithwatermark->displayoptions);
            $width = empty($options['popupwidth']) ? 620 : $options['popupwidth'];
            $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
            $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
            $extra = "onclick=\"window.open('$fullurl', '', '$wh'); return false;\"";
            echo filewithwatermark_get_clicktoopen($file, $filewithwatermark->revision, $extra);
            break;

        case \mod_filewithwatermark\fileutil::$DISPLAY_NEW:
            $extra = 'onclick="this.target=\'_blank\'"';
            echo filewithwatermark_get_clicktoopen($file, $filewithwatermark->revision, $extra);
            break;

        case \mod_filewithwatermark\fileutil::$DISPLAY_DOWNLOAD:
            echo filewithwatermark_get_clicktodownload($file, $filewithwatermark->revision);
            break;

        case \mod_filewithwatermark\fileutil::$DISPLAY_OPEN:
        default:
            echo filewithwatermark_get_clicktoopen($file, $filewithwatermark->revision);
            break;
    }
    echo '</div>';

    echo $OUTPUT->footer();
    die;
}

/**
 * Cria um link que o utilizador tem de premir para descarregar o ficheiro
 */
function filewithwatermark_get_clicktodownload($file, $revision)
{
    global $CFG;

    $filename = $file->get_filename();
    $path = '/' . $file->get_contextid() . '/mod_filewithwatermark/content/' . $revision . $file->get_filepath() . $file->get_filename();
    $fullurl = file_encode_url($CFG->wwwroot . '/pluginfile.php', $path, true);

    $string = get_string('clicktodownload', 'filewithwatermark', "<a href=\"$fullurl\">$filename</a>");

    return $string;
}

/**
 * Obtem detalhes do ficheiro
 */
function filewithwatermark_get_file_details($filewithwatermark, $cm)
{
    $options = empty($filewithwatermark->displayoptions) ? array() : @unserialize($filewithwatermark->displayoptions);
    $filedetails = array();
    if (!empty($options['showsize']) || !empty($options['showtype']) || !empty($options['showdate'])) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_filewithwatermark', 'content', 0, 'sortorder DESC, id ASC', false);
        $mainfile = $files ? reset($files) : null;
        if (!empty($options['showsize'])) {
            $filedetails['size'] = 0;
            foreach ($files as $file) {
                $filedetails['size'] += $file->get_filesize();
                if ($file->get_repository_id()) {
                    $filedetails['isref'] = true;
                }
            }
        }
        if (!empty($options['showtype'])) {
            if ($mainfile) {
                $filedetails['type'] = get_mimetype_description($mainfile);
                $filedetails['mimetype'] = $mainfile->get_mimetype();
                // Only show type if it is not unknown.
                if ($filedetails['type'] === get_mimetype_description('document/unknown')) {
                    $filedetails['type'] = '';
                }
            } else {
                $filedetails['type'] = '';
            }
        }
        if (!empty($options['showdate'])) {
            if ($mainfile) {
                if ($mainfile->get_timemodified() > $mainfile->get_timecreated() + 5 * MINSECS) {
                    $filedetails['modifieddate'] = $mainfile->get_timemodified();
                } else {
                    $filedetails['uploadeddate'] = $mainfile->get_timecreated();
                }
                if ($mainfile->get_repository_id()) {
                    $filedetails['isref'] = true;
                }
            } else {
                $filedetails['uploadeddate'] = '';
            }
        }
    }
    return $filedetails;
}

/**
 * Obtém dados do utilizador a partir do ficheiro
 */
function filewithwatermark_get_file_userdata($file)
{

    $filepath = filewithwatermark_create_tempdir();

    $filename = filewithwatermark_generate_filename($file, $filepath);

    $pdf_editor = filewithwatermark_create_watermarkedfile($file, $filepath, $filename);

    $pdf_extractor = new pdfextractor();

    $content = $pdf_extractor->extract_text($pdf_editor->Output('S'));

    $contentarray = explode('/', $content);

    return [
        'name' => trim($contentarray[0]),
        'email' => trim($contentarray[1])
    ];
}