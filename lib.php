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
 * @package mod_learningmap
 * @copyright  2021 Stefan Hanauska <stefan.hanauska@altmuehlnet.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function learningmap_add_instance($data) {
    global $DB;
    return $DB->insert_record("learningmap", $data);
}

function learningmap_update_instance($data) {
    global $DB;
    $data->id = $data->instance;
    return $DB->update_record("learningmap", $data);
}

function learningmap_delete_instance($id) {
    global $DB;

    return $DB->delete_records("learningmap", array("id" => $id));
    // ToDo: Check whether intro files are automatically deleted.
}

/**
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function learningmap_supports($feature) {
    switch($feature) {
        case FEATURE_IDNUMBER:
            return true;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
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
function learningmap_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    require_course_login($course, true, $cm);

    $fullpath = "/$context->id/mod_learningmap/$filearea/".implode('/', $args);

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, false, $options);
}

function learningmap_cm_info_dynamic(cm_info $cm) {
    // Decides whether to display the map on course page or via view.php.
    if ($cm->showdescription == 1) {
        $cm->set_no_view_link(true);

        $cm->set_content(output_learningmap($cm), true);

        $cm->set_extra_classes('label'); // ToDo: Add extra CSS.
    }
}

function output_learningmap(cm_info $cm) {
    global $DB, $USER, $OUTPUT;

    $context = context_module::instance($cm->id);

    $map = $DB->get_record("learningmap", array("id" => $cm->instance));

    $completion = new completion_info($cm->get_course());

    $svg = file_rewrite_pluginfile_URLS(
        $map->intro,
        'pluginfile.php',
        $context->id,
        'mod_learningmap',
        'intro',
        null
    );
    $placestore = json_decode($map->placestore);

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->validateOnParse = true;
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML('<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "'.new moodle_url('/mod/learningmap/svg11.dtd').'">'.$svg);
    $active = [];
    $completedplaces = [];
    $notavailable = [];
    foreach ($placestore->places as $place) {
        if ($place->linkedActivity != null) {
            $link = $dom->getElementById($place->linkId);

            try {
                $placecm = get_fast_modinfo($cm->get_course(), $USER->id)->get_cm($place->linkedActivity);
            } catch (Exception $e) {
                $placecm = false;
            }
            if (!$placecm) {
                array_push($notavailable, $place->id);
            } else {
                if ($link) {
                    $link->setAttribute(
                        'xlink:href',
                        new moodle_url('/mod/'.$placecm->modname.'/view.php', array('id' => $placecm->id))
                    );
                    $title = $dom->getElementById('title' . $place->id);
                    if ($title) {
                        $title->nodeValue = $placecm->get_formatted_name();
                    }
                }
                if (in_array($place->id, $placestore->startingplaces)) {
                    array_push($active, $place->id);
                }
                if ($completion->get_data($placecm, true, $USER->id)->completionstate > 0) {
                    array_push($completedplaces, $place->id);
                    array_push($active, $place->id);
                }
            }
        } else {
            array_push($notavailable, $place->id);
        }
    }
    foreach ($placestore->paths as $path) {
        if (in_array($path->sid, $completedplaces) && !in_array($path->fid, $notavailable)) {
            array_push($active, $path->id, $path->fid);
        }
        if (in_array($path->fid, $completedplaces) && !in_array($path->sid, $notavailable)) {
            array_push($active, $path->id, $path->sid);
        }
    }
    foreach ($active as $a) {
        $domplace = $dom->getElementById($a);
        if ($domplace) {
            $domplace->setAttribute('style', 'visibility: visible;');
        }
    }
    foreach ($completedplaces as $place) {
        $domplace = $dom->getElementById($place);
        if ($domplace) {
            $domplace->setAttribute('style', 'visibility: visible; fill: green;');
        }
    }
    foreach ($notavailable as $place) {
        $domplace = $dom->getElementById($place);
        if ($domplace) {
            $domplace->setAttribute('style', 'visibility: hidden;');
        }
    }
    $remove = ['<?xml version="1.0"?>',
    '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "'. new moodle_url('/mod/learningmap/svg11.dtd').'">'];
    return(
        $OUTPUT->render_from_template(
            'mod_learningmap/mapcontainer',
            ['mapcode' => str_replace($remove, '', $dom->saveXML())]
            //, 'style' => 'height: ' . $placestore->height . 'px;']
        )
    );
}
