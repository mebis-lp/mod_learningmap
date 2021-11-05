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
 * Editing form for mod_learningmap
 *
 * @package mod_learningmap
 * @copyright  2021 Stefan Hanauska <stefan.hanauska@altmuehlnet.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/learningmap/lib.php');

class mod_learningmap_mod_form extends moodleform_mod {
    public function definition() {
        global $PAGE, $OUTPUT;

        $mform = &$this->_form;

        $cm = get_fast_modinfo($this->current->course);

        $activitysel = [];
        // Gets only sections with content.
        foreach ($cm->sections as $sectionnum => $section) {
            $sectioninfo = $cm->get_section_info($sectionnum);
            $s['name'] = $sectioninfo->name;
            if (empty($sectionname)) {
                $sectionname = get_string('section') . ' ' . $sectionnum;
            }
            $s['coursemodules'] = [];
            foreach ($section as $cmid) {
                $module = $cm->get_cm($cmid);
                if ($module->completion > 0) {
                    array_push($s['coursemodules'], ['id' => $cmid, 'name' => $module->name]);
                }
            }
            array_push($activitysel, $s);
        }

        $mform->addElement('text', 'name', get_string('mapname', 'learningmap'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('html', $OUTPUT->render_from_template('mod_learningmap/formitem', ['sections' => $s]));

        $mform->addElement(
            'filemanager',
            'introeditor[itemid]',
            get_string('backgroundfile', 'learningmap'),
            null,
            array('accepted_types' => 'image', 'maxfiles' => 1)
        );
        $mform->addRule('introeditor[itemid]', null, 'required', null, 'client');

        $mform->addElement('textarea', 'introeditor[text]', get_string('svgcode', 'learningmap'), array("width" => 100));
        $mform->setType('introeditor[text]', PARAM_RAW);

        $mform->addElement('hidden', 'placestore');
        $mform->setType('placestore', PARAM_RAW);

        $mform->addElement('hidden', 'introeditor[format]', FORMAT_HTML);
        $mform->setType('introeditor[format]', PARAM_INT);

        $mform->addElement('hidden', 'showdescription', 1);
        $mform->setType('showdescription', PARAM_INT);

        $PAGE->requires->js_call_amd('mod_learningmap/learningmap', 'init');

        $this->standard_coursemodule_elements();

        $this->add_action_buttons(true, false, null);
    }

    public function data_preprocessing(&$defaultvalues) {
        global $OUTPUT;

        if (!$this->current->instance) {
            $defaultvalues['introeditor[text]'] = $OUTPUT->render_from_template(
                'mod_learningmap/svgskeleton',
                ['placecolor' => 'red', 'strokecolor' => 'white', 'editmode' => true]
            );
        } else {
            $defaultvalues['intro'] = preg_replace(
                '/<style[\s\S]*style>/i',
                $OUTPUT->render_from_template('mod_learningmap/cssskeleton',
                    array_merge(json_decode($defaultvalues['placestore'], true), ['editmode' => true] )),
                $defaultvalues['introeditor']['text']
            );
            $defaultvalues['introeditor']['text'] = $defaultvalues['intro'];
        }
    }

    public function data_postprocessing($data) {
        global $OUTPUT;

        $data->introeditor['text'] = preg_replace(
            '/<style[\s\S]*style>/i',
            $OUTPUT->render_from_template('mod_learningmap/cssskeleton',
                array_merge(json_decode($data->placestore, true), ['editmode' => false])),
            $data->introeditor['text']
        );
    }
}
