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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/learningmap/lib.php');

/**
 * Editing form for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright   2021, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_learningmap_mod_form extends moodleform_mod {
    /**
     * Defines the editing form for mod_learningmap
     *
     * @return void
     */
    public function definition() : void {
        global $PAGE, $OUTPUT;

        $mform = &$this->_form;

        $cm = get_fast_modinfo($this->current->course);

        $s = [];
        $activitysel = [];
        // Gets only sections with content.
        foreach ($cm->sections as $sectionnum => $section) {
            $sectioninfo = $cm->get_section_info($sectionnum);
            $s['name'] = $sectioninfo->name;
            if (empty($s['name'])) {
                $s['name'] = get_string('section') . ' ' . $sectionnum;
            }
            $s['coursemodules'] = [];
            foreach ($section as $cmid) {
                $module = $cm->get_cm($cmid);
                if ($module->completion > 0 && $module->deletioninprogress == 0) {
                    array_push($s['coursemodules'], ['id' => $cmid, 'name' => $module->name]);
                }
            }
            array_push($activitysel, $s);
        }

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'learningmap'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addHelpButton('name', 'name', 'learningmap');

        $mform->addElement(
            'html',
            $OUTPUT->render_from_template(
                'mod_learningmap/formitem',
                ['sections' => $s, 'help' => $OUTPUT->help_icon('intro', 'learningmap', '')]
            )
        );

        $mform->addElement('checkbox', 'showdescription', get_string('showdescription', 'learningmap'));
        $mform->setType('showdescription', PARAM_INT);
        $mform->addHelpButton('showdescription', 'showdescription', 'learningmap');

        $mform->addElement(
            'filemanager',
            'introeditor[itemid]',
            get_string('backgroundfile', 'learningmap'),
            null,
            [
                'accepted_types' => 'web_image',
                'maxfiles' => 1,
                'subdirs' => 0
            ]
        );
        $mform->addRule('introeditor[itemid]', null, 'required', null, 'client');
        $mform->addHelpButton('introeditor[itemid]', 'backgroundfile', 'learningmap');

        $mform->addElement('textarea', 'introeditor[text]', get_string('svgcode', 'learningmap'));
        $mform->setType('introeditor[text]', PARAM_RAW);

        $mform->addElement('hidden', 'placestore');
        $mform->setType('placestore', PARAM_RAW);

        $mform->addElement('hidden', 'introeditor[format]', FORMAT_HTML);
        $mform->setType('introeditor[format]', PARAM_INT);

        $mform->closeHeaderBefore('header');

        $PAGE->requires->js_call_amd('mod_learningmap/learningmap', 'init');

        $this->standard_coursemodule_elements();

        $this->add_action_buttons(true, false, null);
    }

    /**
     * Returns whether the custom completion rules are enabled.
     *
     * @param array $data form data
     * @return bool
     */
    public function completion_rule_enabled($data) : bool {
        return (!empty($data['completiontype']) && $data['completiontype'] > 0);
    }

    /**
     * Adds the custom completion rules for mod_learningmap
     *
     * @return array
     */
    public function add_completion_rules() : array {
        $mform = $this->_form;

        $completionoptions = [
            get_string('nocompletion', 'learningmap'),
            get_string('completion_with_one_target', 'learningmap'),
            get_string('completion_with_all_targets', 'learningmap'),
            get_string('completion_with_all_places', 'mod_learningmap')
        ];

        $mform->addElement(
            'select',
            'completiontype',
            get_string('completiontype', 'learningmap'),
            $completionoptions,
            []
        );

        $mform->setType('completiontype', PARAM_INT);
        $mform->hideIf('completiontype', 'completion', 'neq', COMPLETION_TRACKING_AUTOMATIC);

        return(['completiontype']);
    }

    /**
     * Processes the form data before loading the form. Adds the default values for empty forms, replaces the CSS
     * with the values for editing.
     *
     * @param array $defaultvalues
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) : void {
        global $OUTPUT;

        if (!$this->current->instance) {
            $mapid = uniqid();
            $options = ['editmode' => true, 'mapid' => $mapid];
            $defaultvalues['introeditor[text]'] = $OUTPUT->render_from_template(
                'mod_learningmap/svgskeleton',
                $options
            );
            $defaultvalues['showdescription'] = 1;
            $defaultvalues['placestore'] = json_encode($options);
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

    /**
     * Processes the form data after the form is submitted.
     * Replaces the CSS in the SVG with the parts suitable for output.
     *
     * @param stdClass $data
     * @return void
     */
    public function data_postprocessing($data) : void {
        global $OUTPUT;

        $data->introeditor['text'] = preg_replace(
            '/<style[\s\S]*style>/i',
            $OUTPUT->render_from_template('mod_learningmap/cssskeleton',
                array_merge(json_decode($data->placestore, true), ['editmode' => false])),
            $data->introeditor['text']
        );
    }
}
