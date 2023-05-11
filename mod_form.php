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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/moodlewatermark/locallib.php');
require_once($CFG->dirroot . '/mod/moodlewatermark/classes/fileutil.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/upload/block_upload.php');
require_once($CFG->dirroot . '/lib/form/watermarkmanager.php');
use mod_moodlewatermark\fileutil;

/**
 * Definição do formulário para o módulo
 *
 * @package    mod_moodlewatermark
 * @copyright 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_moodlewatermark_mod_form extends moodleform_mod
{

    /**
     * Define o formulario, campos e opções
     */

    function definition()
    {

        global $CFG, $DB, $OUTPUT;

        $mform =& $this->_form;
        $config = get_config('moodlewatermark');

        $mform->addElement('header', 'general', get_string('general', 'form'));


        $mform->addElement('text', 'name', get_string('name', 'moodlewatermark'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();
        $element = $mform->getElement('introeditor');
        $attributes = $element->getAttributes();
        $attributes['rows'] = 5;
        $element->setAttributes($attributes);

        $filemanager_options = array();
        $filemanager_options['accepted_types'] = 'pdf';
        $filemanager_options['maxbytes'] = 0;
        $filemanager_options['maxfiles'] = 1;
        $filemanager_options['mainfile'] = true;
        $filemanager_options['before_file_save'] = 'fileswithwatermark_before_file_save';
        $mform->addElement('filemanager', 'files', get_string('selectfiles'), null, $filemanager_options);

        //-------------------------------------------------------
        $mform->addElement('header', 'optionssection', get_string('appearance'));

        if ($this->current->instance) {
            $options = \mod_moodlewatermark\fileutil::get_displayoptions(explode(',', $config->displayoptions), $this->current->display);
        } else {
            $options = \mod_moodlewatermark\fileutil::get_displayoptions(explode(',', $config->displayoptions));
        }

        if (count($options) == 1) {
            $mform->addElement('hidden', 'display');
            $mform->setType('display', PARAM_INT);
            reset($options);
            $mform->setDefault('display', key($options));
        } else {
            $mform->addElement('select', 'display', get_string('displayselect', 'moodlewatermark'), $options);
            $mform->setDefault('display', $config->display);
            $mform->addHelpButton('display', 'displayselect', 'moodlewatermark');
        }

        $mform->addElement('checkbox', 'showsize', get_string('showsize', 'moodlewatermark'));
        $mform->setDefault('showsize', $config->showsize);
        $mform->addHelpButton('showsize', 'showsize', 'moodlewatermark');
        $mform->addElement('checkbox', 'showtype', get_string('showtype', 'moodlewatermark'));
        $mform->setDefault('showtype', $config->showtype);
        $mform->addHelpButton('showtype', 'showtype', 'moodlewatermark');
        $mform->addElement('checkbox', 'showdate', get_string('showdate', 'moodlewatermark'));
        $mform->setDefault('showdate', $config->showdate);
        $mform->addHelpButton('showdate', 'showdate', 'moodlewatermark');

        if (array_key_exists(fileutil::$DISPLAY_POPUP, $options)) {
            $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'moodlewatermark'), array('size' => 3));
            if (count($options) > 1) {
                $mform->hideIf('popupwidth', 'display', 'noteq', \mod_moodlewatermark\fileutil::$DISPLAY_POPUP);
            }
            $mform->setType('popupwidth', PARAM_INT);
            $mform->setDefault('popupwidth', $config->popupwidth);
            $mform->setAdvanced('popupwidth', true);

            $mform->addElement('text', 'popupheight', get_string('popupheight', 'moodlewatermark'), array('size' => 3));
            if (count($options) > 1) {
                $mform->hideIf('popupheight', 'display', 'noteq', \mod_moodlewatermark\fileutil::$DISPLAY_POPUP);
            }
            $mform->setType('popupheight', PARAM_INT);
            $mform->setDefault('popupheight', $config->popupheight);
            $mform->setAdvanced('popupheight', true);
        }

        if (
            array_key_exists(fileutil::$DISPLAY_AUTO, $options) or
            array_key_exists(fileutil::$DISPLAY_EMBED, $options) or
            array_key_exists(fileutil::$DISPLAY_FRAME, $options)
        ) {
            $mform->addElement('checkbox', 'printintro', get_string('printintro', 'moodlewatermark'));
            $mform->hideIf('printintro', 'display', 'eq', \mod_moodlewatermark\fileutil::$DISPLAY_POPUP);
            $mform->hideIf('printintro', 'display', 'eq', \mod_moodlewatermark\fileutil::$DISPLAY_DOWNLOAD);
            $mform->hideIf('printintro', 'display', 'eq', \mod_moodlewatermark\fileutil::$DISPLAY_OPEN);
            $mform->hideIf('printintro', 'display', 'eq', \mod_moodlewatermark\fileutil::$DISPLAY_NEW);
            $mform->setDefault('printintro', $config->printintro);
        }

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();

        //-------------------------------------------------------
        $mform->addElement('hidden', 'revision');
        $mform->setType('revision', PARAM_INT);
        $mform->setDefault('revision', 1);
    }


    /**
     * Define as opções e valores por defeito, se estiverem vazios.
     *
     */

    function data_preprocessing(&$default_values)
    {

        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('files');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_moodlewatermark', 'content', 0, array('subdirs' => true));
            $default_values['files'] = $draftitemid;
        }

        if (!empty($default_values['displayoptions'])) {
            $displayoptions = unserialize($default_values['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $default_values['printintro'] = $displayoptions['printintro'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $default_values['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $default_values['popupheight'] = $displayoptions['popupheight'];
            }
            if (!empty($displayoptions['showsize'])) {
                $default_values['showsize'] = $displayoptions['showsize'];
            } else {
                $default_values['showsize'] = 0;
            }
            if (!empty($displayoptions['showtype'])) {
                $default_values['showtype'] = $displayoptions['showtype'];
            } else {
                $default_values['showtype'] = 0;
            }
            if (!empty($displayoptions['showdate'])) {
                $default_values['showdate'] = $displayoptions['showdate'];
            } else {
                $default_values['showdate'] = 0;
            }
        }

    }

    /**
     * Valida os ficheiros e dados submetidos no formulário
     */

    function validation($data, $files)
    {

        global $USER;

        $pdfversions = [
            'PDF\-1\.0',
            'PDF\-1\.1',
            'PDF\-1\.2',
            'PDF\-1\.3',
            'PDF\-1\.4'
        ];
        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data['files'], 'sortorder, id', false);
        $invalidversion = false;

        foreach ($files as $file) {
            $content = $file->get_content();
            if (!preg_match_all("/(" . join("|", $pdfversions) . ")/", $content)) {
                $errors['files'] = get_string('versionnotallowed', 'moodlewatermark');
                $invalidversion = true;
                break;
            }


            $errors = parent::validation($data, $files);

            $usercontext = context_user::instance($USER->id);

            $fs = get_file_storage();

            $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data['files'], 'sortorder, id', false);
            $invalidversion = false;

            foreach ($files as $file) {
                $content = $file->get_content();
                if (!preg_match_all("/(" . join("|", $pdfversions) . ")/", $content)) {
                    $errors['files'] = get_string('versionnotallowed', 'moodlewatermark');
                    $invalidversion = true;
                    break;
                }
            }

            if ($invalidversion) {
                return $errors;
            }

            if (!$files) {
                $errors['files'] = get_string('required');
                return $errors;
            }

            if (count($files) === 1) {
                return $errors;
            }

            $hasMainFiles = false;

            foreach ($files as $file) {
                if ($file->get_sortorder() === 1) {
                    $hasMainFiles = true;
                    break;
                }
            }

            if (!$hasMainFiles) {
                $file = reset($files);
                file_set_sortorder(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename(),
                    1
                );
            }

            return $errors;

        }
    }


}