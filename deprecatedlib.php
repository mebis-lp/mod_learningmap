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
 * Deprecated mod_learningmap functions. Kept for compatibility with moodle 3.9.
 *
 * @package     mod_learningmap
 * @copyright   2021-2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Constants for completion. Updates need also to happen in custom_completion class.

/**
 * No custom completion.
 */
define('LEARNINGMAP_NOCOMPLETION', 0);
/**
 * Activity is completed when one target place is reached.
 */
define('LEARNINGMAP_COMPLETION_WITH_ONE_TARGET', 1);
/**
 * Activity is completed when all target places are reached.
 */
define('LEARNINGMAP_COMPLETION_WITH_ALL_TARGETS', 2);
/**
 * Activity is completed when all places are reached.
 */
define('LEARNINGMAP_COMPLETION_WITH_ALL_PLACES', 3);

/**
 * Return whether the learning map is completed by the given user.
 * Necessary for moodle 3.9 / 3.10.
 *
 * @deprecated since Moodle 3.11
 * @see \mod_learningmap\completion\custom_completion
 * @param \stdClass $course Course
 * @param \cm_info $cm course module
 * @param int $userid User ID
 * @param bool $type Type of comparison
 * @return bool True if completed, false if not.
 */
function learningmap_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    $map = $DB->get_record("learningmap", ["id" => $cm->instance], 'completiontype, placestore', MUST_EXIST);

    if ($map->completiontype == LEARNINGMAP_NOCOMPLETION) {
        return $type;
    } else {
        $user = \core_user::get_user($userid);
        $group = (empty($cm->groupmode) ? 0 : groups_get_activity_group($cm, true));
        $activitymanager = new mod_learningmap\activitymanager($course, $user, $group);

        $placestore = json_decode($map->placestore);

        // Return COMPLETION_INCOMPLETE if there are no target places and condition requires to have one.
        if (
            ($map->completiontype < LEARNINGMAP_COMPLETION_WITH_ALL_PLACES) &&
            count($placestore->targetplaces) == 0
        ) {
            return COMPLETION_INCOMPLETE;
        }

        $modinfo = get_fast_modinfo($course, $userid);
        $cms = $modinfo->get_cms();
        $allcms = array_keys($cms);

        foreach ($placestore->places as $place) {
            // Prevent infinite loop.
            if ($place->linkedActivity == $cm->id) {
                continue;
            }
            // Skip non-target places when there is no condition to visit all places.
            if (
                $map->completiontype != LEARNINGMAP_COMPLETION_WITH_ALL_PLACES &&
                !in_array($place->id, $placestore->targetplaces)
            ) {
                continue;
            }
            if ($place->linkedActivity != null) {
                if (in_array($place->linkedActivity, $allcms)) {
                    $placecm = $modinfo->get_cm($place->linkedActivity);
                } else {
                    // No way to fulfill condition.
                    if ($map->completiontype > LEARNINGMAP_COMPLETION_WITH_ONE_TARGET) {
                        return COMPLETION_INCOMPLETE;
                    }
                    $placecm = false;
                }

                if (
                    !$placecm ||
                    !$activitymanager->is_completed($placecm)
                ) {
                    // No way to fulfill condition.
                    if ($map->completiontype > LEARNINGMAP_COMPLETION_WITH_ONE_TARGET) {
                        return COMPLETION_INCOMPLETE;
                    }
                } else {
                    // We need only one.
                    if (
                        $map->completiontype == LEARNINGMAP_COMPLETION_WITH_ONE_TARGET &&
                        $activitymanager->is_completed($placecm)
                    ) {
                        return COMPLETION_COMPLETE;
                    }
                }
            } else {
                // No way to fulfill condition.
                if ($map->completiontype > LEARNINGMAP_COMPLETION_WITH_ONE_TARGET) {
                    return COMPLETION_INCOMPLETE;
                }
            }
        }
        if ($map->completiontype == LEARNINGMAP_COMPLETION_WITH_ONE_TARGET) {
            return COMPLETION_INCOMPLETE;
        } else {
            return COMPLETION_COMPLETE;
        }
    }
}
