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

use mod_learningmap\completion\custom_completion;
use mod_learningmap\mapworker;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/learningmap/lib.php');

/**
 * Editing form for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright   2021-2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_learningmap_mod_form extends moodleform_mod {
    /**
     * Defines the editing form for mod_learningmap
     *
     * @return void
     */
    public function definition(): void {
        global $PAGE, $OUTPUT;

        $mform = &$this->_form;

        $cm = get_fast_modinfo($this->current->course);

        $s = [];
        $activitysel = [];
        // Gets only sections with content.
        foreach ($cm->get_sections() as $sectionnum => $section) {
            $sectioninfo = $cm->get_section_info($sectionnum);
            $s['name'] = $sectioninfo->name;
            if (empty($s['name'])) {
                $s['name'] = get_string('section') . ' ' . $sectionnum;
            }
            $s['coursemodules'] = [];
            foreach ($section as $cmid) {
                $module = $cm->get_cm($cmid);
                // Get only course modules which are not deleted.
                if ($module->deletioninprogress == 0) {
                    $s['coursemodules'][] = [
                        'id' => $cmid,
                        'name' => s($module->name),
                        'completionenabled' => $module->completion > 0,
                        'hidden' => $module->visible == 0,
                    ];
                }
            }
            $activitysel[] = $s;
        }

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'learningmap'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addHelpButton('name', 'name', 'learningmap');

        $mform->addElement(
            'html',
            $OUTPUT->render_from_template(
                'mod_learningmap/inlinehelp',
                ['usecaselink' => get_config('mod_learningmap', 'usecaselink')]
            )
        );

        $features = [];
        foreach (LEARNINGMAP_FEATURES as $feature) {
            $features[] = [
                'name' => $feature,
                'title' => get_string($feature, 'learningmap'),
                'text' => get_string($feature . '_help', 'learningmap'),
                'alt' => get_string('help'),
            ];
        }
        $mform->addElement(
            'html',
            $OUTPUT->render_from_template(
                'mod_learningmap/formitem',
                ['sections' => $activitysel,
                'help' => $OUTPUT->help_icon('intro', 'learningmap', ''),
                'completiondisabled' => $cm->get_course()->enablecompletion == 0,
                'features' => $features,
                ]
            )
        );

        $mform->addElement('checkbox', 'showdescription', get_string('showdescription', 'learningmap'));
        $mform->setType('showdescription', PARAM_INT);
        $mform->addHelpButton('showdescription', 'showdescription', 'learningmap');

        $mform->addElement('checkbox', 'backlink', get_string('showbacklink', 'learningmap'));
        $mform->setType('backlink', PARAM_INT);
        $mform->addHelpButton('backlink', 'showbacklink', 'learningmap');

        $mform->addElement(
            'filemanager',
            'introeditor[itemid]',
            get_string('backgroundfile', 'learningmap'),
            null,
            [
                'accepted_types' => 'web_image',
                'maxfiles' => 1,
                'subdirs' => 0,
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

        $mform->addHelpButton('groupmode', 'groupmode', 'learningmap');
    }

    /**
     * Remove visible groups here to avoid warning
     *
     * @return void
     */
    public function definition_after_data() {
        $this->_form->_elements[$this->_form->_elementIndex['groupmode']]->removeOption(VISIBLEGROUPS);
        parent::definition_after_data();
    }

    /**
     * Returns whether the custom completion rules are enabled.
     *
     * @param array $data form data
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        return (!empty($data['completiontype']) && $data['completiontype'] > 0);
    }

    /**
     * Adds the custom completion rules for mod_learningmap
     *
     * @return array
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;

        $completionoptions = [
            custom_completion::NOCOMPLETION => get_string('nocompletion', 'learningmap'),
            custom_completion::COMPLETION_WITH_ONE_TARGET => get_string('completion_with_one_target', 'learningmap'),
            custom_completion::COMPLETION_WITH_ALL_TARGETS => get_string('completion_with_all_targets', 'learningmap'),
            custom_completion::COMPLETION_WITH_ALL_PLACES => get_string('completion_with_all_places', 'mod_learningmap'),
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
    public function data_preprocessing(&$defaultvalues): void {
        global $OUTPUT;

        // Initialize a new learningmap instance.
        if (!$this->current->instance) {
            // Every map gets a unique id for applying CSS.
            $mapid = uniqid();
            // The editmode setting loads the CSS styles for the editor.
            $options = ['editmode' => true, 'mapid' => $mapid];
            // Loads the SVG template to the textarea for the introeditor.
            // The textarea is hidden in the browser.
            $defaultvalues['introeditor[text]'] = $OUTPUT->render_from_template(
                'mod_learningmap/svgskeleton',
                $options
            );
            // Default behaviour is to act as a label.
            $defaultvalues['showdescription'] = 1;
            // Encodes the base settings as json. Further default settings are
            // generated by javascript to avoid duplicate code.
            $defaultvalues['placestore'] = json_encode($options);
        } else {
            $context = context_module::instance($defaultvalues['coursemodule']);

            $defaultvalues['intro'] = file_rewrite_pluginfile_URLS(
                $defaultvalues['intro'],
                'pluginfile.php',
                $context->id,
                'mod_learningmap',
                'intro',
                null
            );
            $modinfo = get_fast_modinfo($defaultvalues['course']);
            $cm = $modinfo->get_cm($defaultvalues['coursemodule']);
            // Replace the stylesheet for editing mode.
            $mapworker = new mapworker(
                $defaultvalues['intro'],
                json_decode($defaultvalues['placestore'], true),
                $cm,
                true
            );
            $mapworker->process_map_objects();
            $mapworker->replace_stylesheet();
            $defaultvalues['intro'] = $mapworker->get_svgcode();
            // Make the introeditor use the values of the intro field.
            // This is necessary to avoid inconsistencies.
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
    public function data_postprocessing($data): void {
        $mapworker = new mapworker(
            $data->introeditor['text'],
            json_decode($data->placestore, true)
        );
        $mapworker->replace_stylesheet();
        $data->introeditor['text'] = $mapworker->get_svgcode();
    }
}
