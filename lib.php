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
 * Library for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright   2021-2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/deprecatedlib.php');

/**
 * Array with all features the plugin supports for advanced settings. Might be moved
 * to another place when in use somewhere else.
 */
define('LEARNINGMAP_FEATURES', [
    'hidepaths',
    'hidestroke',
    'usecheckmark',
    'pulse',
    'hover',
    'showall',
    'showtext',
    'slicemode',
    'showwaygone',
]);

/**
 * Adds a new learningmap instance
 *
 * @param stdClass $data learningmap record
 * @return int
 */
function learningmap_add_instance($data) : int {
    global $DB;
    return $DB->insert_record("learningmap", $data);
}

/**
 * Updates a learningmap instance
 *
 * @param stdClass $data learningmap record
 * @return int
 */
function learningmap_update_instance($data) : int {
    global $DB;
    $data->id = $data->instance;
    return $DB->update_record("learningmap", $data);
}

/**
 * Deletes a learningmap instance
 *
 * @param integer $id learningmap record
 * @return int
 */
function learningmap_delete_instance($id) : int {
    global $DB;

    return $DB->delete_records("learningmap", ["id" => $id]);
    // ToDo: Check whether intro files are automatically deleted.
}

/**
 * Returns whether a feature is supported by this module.
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function learningmap_supports($feature) {
    // For versions <4.0.
    if (!defined('FEATURE_MOD_PURPOSE')) {
        define('FEATURE_MOD_PURPOSE', 'mod_purpose');
        define('MOD_PURPOSE_CONTENT', 'content');
    }
    switch($feature) {
        case FEATURE_IDNUMBER:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Delivers the background file
 *
 * @package mod_learningmap
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function learningmap_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=[]) : ?bool {
    require_course_login($course, true, $cm);

    $fullpath = "/$context->id/mod_learningmap/$filearea/".implode('/', $args);

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, false, $options);
}

/**
 * Adds custom completion info to the course module info
 *
 * @param cm_info $cm
 * @return cached_cm_info|null
 */
function learningmap_get_coursemodule_info($cm) : cached_cm_info {
    global $DB;

    if (!$map = $DB->get_record('learningmap', ['id' => $cm->instance], 'completiontype')) {
        return null;
    }

    $result = new cached_cm_info();

    $completiontypes = [
        'nocompletion',
        'completion_with_one_target',
        'completion_with_all_targets',
        'completion_with_all_places'
    ];

    if ($cm->completion == COMPLETION_TRACKING_AUTOMATIC && $map->completiontype > 0) {
        $result->customdata['customcompletionrules'][$completiontypes[$map->completiontype]] = 1;
    }

    return $result;
}

/**
 * Removes the view link if showdescription is set.
 *
 * @param cm_info $cm
 * @return void
 */
function learningmap_cm_info_dynamic(cm_info $cm) : void {
    // Decides whether to display the link.
    if ($cm->showdescription == 1) {
        $cm->set_no_view_link(true);
    }
}

/**
 * Generates course module info, especially the map (as intro).
 * If showdescription is not set, this function does nothing.
 *
 * @param cm_info $cm
 * @return void
 */
function learningmap_cm_info_view(cm_info $cm) : void {
    global $CFG, $OUTPUT, $PAGE;
    // Only show map on course page if showdescription is set.
    if ($cm->showdescription == 1) {
        $groupdropdown = '';
        if (!empty($cm->groupmode)) {
            $groupdropdown = groups_print_activity_menu(
                $cm,
                new moodle_url(
                    '/course/view.php',
                    ['id' => $cm->get_course()->id, 'section' => $cm->sectionnum],
                    'module-' . $cm->id
                ),
                true
            );
            // Since there is no way to replace the core string just for this dropdown
            // we have to change it in this ugly way.
            $groupdropdown = str_replace(
                get_string('allparticipants'),
                get_string('ownprogress', 'mod_learningmap'),
                $groupdropdown
            );
        }

        $enableliveupdatercomponent = true;
        if ($CFG->branch < 400) {
            // Only in moodle <4.0 we call this separate manual completion watcher.
            // From moodle 4.0 on this is handled by the mustache loader.
            $PAGE->requires->js_call_amd('mod_learningmap/manual-completion-watch', 'init',
                ['coursemodules' => learningmap_get_place_cm($cm)]);
            // Disable the live updater (a reactive component which only works with moodle >=4.0).
            $enableliveupdatercomponent = false;
        }
        $content = $OUTPUT->render_from_template('mod_learningmap/rendercontainer',
            ['cmId' => $cm->id, 'enableLiveUpdater' => $enableliveupdatercomponent]);

        $cm->set_content($groupdropdown . $content, true);
        $cm->set_extra_classes('label'); // ToDo: Add extra CSS.
        // This method check is needed to provide backwards compatibility to moodle versions below 4.0.
        if (method_exists($cm, 'set_custom_cmlist_item')) {
            $cm->set_custom_cmlist_item(true);
        }
    }
}

/**
 * Returns all course module ids for places of a certain learning map.
 * @param cm_info $cm course module object for the learning map
 * @return array
 */
function learningmap_get_place_cm(cm_info $cm) : array {
    global $DB;
    $map = $DB->get_record("learningmap", ["id" => $cm->instance], 'placestore');
    $modules = [];
    $placestore = json_decode($map->placestore);
    foreach ($placestore->places as $p) {
        if ($p->linkedActivity != null) {
            $modules[] = $p->linkedActivity;
        }
    }
    return $modules;
}

/**
 * Returns the code of the learningmap.
 *
 * @param cm_info $cm
 * @return string
 */
function learningmap_get_learningmap(cm_info $cm) : string {
    global $DB, $OUTPUT, $PAGE;

    $context = context_module::instance($cm->id);

    $map = $DB->get_record("learningmap", ["id" => $cm->instance]);

    $svg = file_rewrite_pluginfile_URLS(
        $map->intro,
        'pluginfile.php',
        $context->id,
        'mod_learningmap',
        'intro',
        null
    );

    $placestore = json_decode($map->placestore, true);

    $group = (empty($cm->groupmode) ? 0 : groups_get_activity_group($cm, true));

    $worker = new \mod_learningmap\mapworker($svg, $placestore, $cm, false, $group);
    $worker->process_map_objects();
    $worker->remove_tags_before_svg();

    $allowedfilters = explode(',', str_replace(' ', '', get_config('mod_learningmap', 'allowedfilters')));

    $filtermanager = filter_manager::instance();
    $skipfilters = array_diff(array_keys(filter_get_active_in_context($cm->context)), $allowedfilters);

    return(
        $filtermanager->filter_text(
            $OUTPUT->render_from_template(
                'mod_learningmap/mapcontainer',
                ['mapcode' => $worker->get_svgcode()]
            ),
            $cm->context,
            ['trusted' => true, 'noclean' => true],
            $skipfilters
        )
    );
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * At this moment nothing needs to be done.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function learningmap_reset_userdata($data) {
    return [];
}
