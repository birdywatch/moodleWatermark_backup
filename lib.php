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
 * @package    mod_moodlewatermark
 * @copyright
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/moodlewatermark/vendor/autoload.php');
require_once($CFG->dirroot . '/mod/moodlewatermark/classes/fileutil.php');
require_once($CFG->dirroot . '/config.php');
require_once($CFG->libdir . '/datalib.php');
require_once($CFG->dirroot . '/lib/filelib.php');

use DateTime;
use DateTimeZone;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Lista das funcionalidades do modulo
 */
function moodlewatermark_supports($feature)
{
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;

        default:
            return null;
    }
}

/**
 * Adiciona uma nova instancia do modulo
 */
function moodlewatermark_add_instance($data, $moodlewatermark)
{
    global $CFG, $DB;

    require_once("$CFG->dirroot/mod/moodlewatermark/locallib.php");

    $cmid = $data->coursemodule;
    $data->timemodified = time();

    moodlewatermark_set_display_options($data);

    $data->id = $DB->insert_record('moodlewatermark', $data);
    $DB->set_field('course_modules', 'instance', $data->id, array('id' => $cmid));
    moodlewatermark_set_mainfile($data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($cmid, 'moodlewatermark', $data->id, $completiontimeexpected);

    return $data->id;

}

/**
 * Atualiza uma instancia existente
 */
function moodlewatermark_update_instance($data)
{
    global $CFG, $DB;

    require_once("$CFG->dirroot/mod/moodlewatermark/locallib.php");

    $data->timemodified = time();
    $data->id = $data->instance;
    $data->revision++;

    moodlewatermark_set_display_options($data);

    $DB->update_record('moodlewatermark', $data);
    moodlewatermark_set_mainfile($data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'moodlewatermark', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Apaga uma instancia existente
 */
function moodlewatermark_delete_instance($id)
{
    global $DB;

    if (!$moodlewatermark = $DB->get_record('moodlewatermark', array('id' => $id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('moodlewatermark', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'moodlewatermark', $id, null);


    $DB->delete_records('moodlewatermark', array('id' => $moodlewatermark->id));

    return true;

}

/**
 * Fornece o ficheiro pedido pelo cliente
 */
function moodlewatermark_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array())
{
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if (!has_capability('mod/moodlewatermark:view', $context)) {
        return false;
    }

    if ($filearea !== 'content') {
        return false;
    }

    array_shift($args);

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = rtrim("/$context->id/mod_moodlewatermark/$filearea/0/$relativepath", '/');

    do {
        if (!$file = $fs->get_file_by_hash(sha1($fullpath))) {
            if ($fs->get_file_by_hash(sha1("$fullpath/."))) {
                if ($file = $fs->get_file_by_hash(sha1("$fullpath/index.htm"))) {
                    break;
                }
                if ($file = $fs->get_file_by_hash(sha1("$fullpath/index.html"))) {
                    break;
                }
                if ($file = $fs->get_file_by_hash(sha1("$fullpath/Default.htm"))) {
                    break;
                }
            }
            $moodlewatermark = $DB->get_record('moodlewatermark', array('id' => $cm->instance), 'id, legacyfiles', MUST_EXIST);
            if ($moodlewatermark->legacyfiles != \mod_moodlewatermark\fileutil::$LEGACYFILES_ACTIVE) {
                return false;
            }
            if (!$file = \mod_moodlewatermark\fileutil::try_file_migration('/' . $relativepath, $cm->id, $cm->course, 'mod_moodlewatermark', 'content', 0)) {
                return false;
            }
            $moodlewatermark->legacyfileslast = time();
            $DB->update_record('moodlewatermark', $moodlewatermark);
        }
    } while (false);

    moodlewatermark_add_watermark($file, $forcedownload);

}

/**
 * Marca a atividade como concluida/visualizada
 */
function moodlewatermark_view($moodlewatermark, $course, $cm, $context)
{

    $params = array(
        'context' => $context,
        'objectid' => $moodlewatermark->id
    );

    $event = \mod_moodlewatermark\event\course_module_viewed::create($params);

    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('moodlewatermark', $moodlewatermark);
    $event->trigger();
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Atualiza as opções de visualização dependendo da informação recebida pelo formulário.
 */
function moodlewatermark_set_display_options($data)
{
    $displayoptions = array();
    if ($data->display == \mod_moodlewatermark\fileutil::$DISPLAY_POPUP) {
        $displayoptions['popupwidth'] = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    if (in_array($data->display, array(\mod_moodlewatermark\fileutil::$DISPLAY_AUTO, \mod_moodlewatermark\fileutil::$DISPLAY_EMBED, \mod_moodlewatermark\fileutil::$DISPLAY_FRAME))) {
        $displayoptions['printintro'] = (int) !empty($data->printintro);
    }
    if (!empty($data->showsize)) {
        $displayoptions['showsize'] = 1;
    }
    if (!empty($data->showtype)) {
        $displayoptions['showtype'] = 1;
    }
    if (!empty($data->showdate)) {
        $displayoptions['showdate'] = 1;
    }
    $data->displayoptions = serialize($displayoptions);
}

/**
 * Adiciona a marca de agua ao ficheiro, recebe o $file representando o ficheiro original e cria uma cópia temporaria com a marca de agua e apresenta.
 */
function moodlewatermark_add_watermark($file, $forcedownload)
{

    $filepath = moodlewatermark_create_tempdir();

    $filename = moodlewatermark_generate_filename($file, $filepath);

    $pdf_editor = moodlewatermark_create_watermarkedfile($file, $filepath, $filename);

    try {

        if ($forcedownload) {
            $pdf_editor->Output('D', $filename);
        } else {
            send_content_uncached($pdf_editor->Output('S'), $filename);
        }
    } catch (Exception $exception) {
        echo get_string('cannotgeneratewatermark', 'mod_moodlewatermark');
    }

}

/**
 * Cria uma cópia do $file, adicionando o seu conteudo, adiciona a marca de agua e devolve a função.
 */

function moodlewatermark_create_watermarkedfile($file, $filepath, $filename)
{

    $pdf_editor = new FPDI();

    try {
        $filestream = fopen($filepath . $filename, 'a+');
        fwrite($filestream, $file->get_content());
        fclose($filestream);

        addwatermark($filepath . $filename);
        $countPage = $pdf_editor->setSourceFile($filepath . $filename);
        for ($i = 1; $i <= $countPage; $i++) {

            $pageId = $pdf_editor->importPage($i);

            $specs = $pdf_editor->getTemplateSize($pageId);

            $pdf_editor->addPage($specs['orientation']);

            $pdf_editor->useImportedPage($pageId);
        }
        unlink($filepath . $filename);

        return $pdf_editor;

    } catch (Exception $e) {
        echo get_string('cannotgeneratewatermark', 'mod_moodlewatermark');
    }
}

/**
 * Adiciona a marca de agua ao ficheiro, e converte, atráves do ghostscript, o pdf a imagem e de volta a pdf, para não ser possivel selecionar ou remover a marca de agua.
 */
function addwatermark($path)
{
    global $USER;

    $pdf = new FPDI();
    $pageCount = $pdf->setSourceFile($path);
    $date = new DateTime('now', new DateTimeZone('Europe/Lisbon'));
    $text = $USER->firstname . ' ' . $USER->lastname . "     |     " . $USER->email . "     |     " . getremoteaddr() . "     |     " . $date->format('d/m/Y H:i:s');
    $watermark = imagecreatetruecolor(1500, 1500);
    imagealphablending($watermark, false);
    $transparency = imagecolorallocatealpha($watermark, 0, 0, 0, 127);
    imagefill($watermark, 0, 0, $transparency);
    imagesavealpha($watermark, true);
    $textColor = imagecolorallocate($watermark, 0, 0, 0);

    for ($i = 100; $i < 3000; $i += 15) {
        imagestring($watermark, 10, 0, $i, $text, $textColor);
    }

    imagefilter($watermark, IMG_FILTER_COLORIZE, 0, 0, 0, 127 * 0.70);
    $watermarkPath = '/var/www/moodle/course/watermarked' . md5(uniqid(mt_rand(), true)) . '.png';
    imagepng($watermark, $watermarkPath);

    $watermark = imagecreatefrompng($watermarkPath);
    imagedestroy($watermark);
    $watermark = imagecreatefrompng($watermarkPath);
    $watermarkWidth = imagesx($watermark);
    $watermarkHeight = imagesy($watermark);
    for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
        $pdf->AddPage();
        $template = $pdf->importPage($pageNumber);
        $pdf->useTemplate($template);
        $pageWidth = $pdf->GetPageWidth();
        $pageHeight = $pdf->GetPageHeight();
        $pdf->Image($watermarkPath, $pageWidth / 6, 0, $watermarkWidth, $watermarkHeight);
    }
    $pdf->Output($path, 'F');
    unlink($watermarkPath);
    $gsPath = '/usr/bin/gs';
    $inputPath = $path;
    $outputPath = $path;

    $command = $gsPath . " -sDEVICE=png16m -o " . $inputPath . "_%03d.png -r200 -dNOPAUSE -dBATCH -dSAFER -dDownScaleFactor=1 " . $inputPath . "  && convert " . $inputPath . "_*.png " . $outputPath . "  && rm " . $inputPath . "_*.png";
    exec($command, $output, $returnCode);

}



/**
 * Gera um nome unico para o ficheiro.
 */
function moodlewatermark_generate_filename($file, $filepath)
{
    $filename = uniqid("moodlewatermark", true) . $file->get_filename();
    ;

    if (file_exists($filepath . $filename)) {
        $filename = uniqid("moodlewatermark", true) . $file->get_filename();
    }

    return $filename;

}

/**
 * Cria um diretório temporário com as permissões necessárias para armazenar os ficheiros
 */
function moodlewatermark_create_tempdir()
{
    global $CFG;

    $tempdir = $CFG->tempdir . '/moodlewatermark';

    if (!file_exists($tempdir)) {
        mkdir($tempdir, 0777, true);
    }

    return $tempdir . '/';
}

/**
 * Devolve uma instancia de um evento do calendário do Moodle e devolve o ficheiro associado a esse evento, caso exista
 */
function mod_moodlewatermark_core_calendar_provide_event_action(
    calendar_event $event,
    \core_calendar\action_factory $factory,
    $userid = 0
) {

    global $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['moodlewatermark'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/moodlewatermark/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

/**
 * Se a instancia atual estiver num curso, devolve possivel informção necessária sobre o curso.
 */
function moodlewatermark_get_coursemodule_info($coursemodule)
{
    global $CFG, $DB;
    require_once("$CFG->libdir/filelib.php");
    require_once("$CFG->dirroot/mod/moodlewatermark/locallib.php");
    require_once($CFG->libdir . '/completionlib.php');

    $context = context_module::instance($coursemodule->id);

    if (
        !$moodlewatermark = $DB->get_record(
            'moodlewatermark',
            array('id' => $coursemodule->instance),
            'id, name, display, displayoptions, revision, intro, introformat'
        )
    ) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $moodlewatermark->name;
    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('moodlewatermark', $moodlewatermark, $coursemodule->id, false);
    }

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_moodlewatermark', 'content', 0, 'sortorder DESC, id ASC', false, 0, 0, 1);
    if (count($files) >= 1) {
        $mainfile = reset($files);
        $info->icon = file_file_icon($mainfile, 24);
        $moodlewatermark->mainfile = $mainfile->get_filename();
    }

    $display = moodlewatermark_get_final_display_type($moodlewatermark);

    if ($display == \mod_moodlewatermark\fileutil::$DISPLAY_POPUP) {
        $fullurl = "$CFG->wwwroot/mod/moodlewatermark/view.php?id=$coursemodule->id&amp;redirect=1";
        $options = empty($moodlewatermark->displayoptions) ? array() : unserialize($moodlewatermark->displayoptions);
        $width = empty($options['popupwidth']) ? 620 : $options['popupwidth'];
        $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
        $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
        $info->onclick = "window.open('$fullurl', '', '$wh'); return false;";

    } else if ($display == \mod_moodlewatermark\fileutil::$DISPLAY_NEW) {
        $fullurl = "$CFG->wwwroot/mod/moodlewatermark/view.php?id=$coursemodule->id&amp;redirect=1";
        $info->onclick = "window.open('$fullurl'); return false;";

    }

    if (($filedetails = moodlewatermark_get_file_details($moodlewatermark, $coursemodule)) && empty($filedetails['isref'])) {
        $displayoptions = @unserialize($moodlewatermark->displayoptions);
        $displayoptions['filedetails'] = $filedetails;
        $info->customdata = serialize($displayoptions);
    } else {
        $info->customdata = $moodlewatermark->displayoptions;
    }

    return $info;
}

/**
 * Mostra detalhes adicionais ao abrir o ficheiros, caso essa opção esteja ativada
 */
function moodlewatermark_cm_info_view(cm_info $cm)
{
    global $CFG;
    require_once($CFG->dirroot . '/mod/moodlewatermark/locallib.php');

    $resource = (object) array('displayoptions' => $cm->customdata);
    $details = moodlewatermark_get_optional_details($resource, $cm);
    if ($details) {
        $cm->set_after_link(
            ' ' . html_writer::tag(
                'span',
                $details,
                array('class' => 'resourcelinkdetails')
            )
        );
    }
}