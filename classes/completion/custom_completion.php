<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_learningmap\completion;

use mod_learningmap\activitymanager;

/**
 * Custom completion rules for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright 2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends \core_completion\activity_custom_completion {
    /**
     * No custom completion.
     */
    const NOCOMPLETION = 0;
    /**
     * Activity is completed when one target place is reached.
     */
    const COMPLETION_WITH_ONE_TARGET = 1;
    /**
     * Activity is completed when all target places are reached.
     */
    const COMPLETION_WITH_ALL_TARGETS = 2;
    /**
     * Activity is completed when all places are reached.
     */
    const COMPLETION_WITH_ALL_PLACES = 3;
    /**
     * Activity worker to handle completion
     * @var activitymanager
     */
    protected activitymanager $activitymanager;

    /**
     * Returns completion state of the custom completion rules
     *
     * @param string $rule
     * @return integer
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $map = $DB->get_record("learningmap", ["id" => $this->cm->instance], 'completiontype, placestore', MUST_EXIST);

        if ($map->completiontype > self::NOCOMPLETION) {
            $user = \core_user::get_user($this->userid);
            if (!$user) {
                return COMPLETION_INCOMPLETE;
            }
            $group = (empty($this->cm->groupmode) ? 0 : groups_get_activity_group($this->cm, true));
            $this->activitymanager = new activitymanager($this->cm->get_course(), $user, $group);

            $placestore = json_decode($map->placestore);

            // Return COMPLETION_INCOMPLETE if there are no target places and condition requires to have one.
            if (
                ($map->completiontype < self::COMPLETION_WITH_ALL_PLACES) &&
                count($placestore->targetplaces) == 0
            ) {
                return COMPLETION_INCOMPLETE;
            }

            $modinfo = get_fast_modinfo($this->cm->get_course(), $this->userid);
            $cms = $modinfo->get_cms();
            $allcms = array_keys($cms);

            foreach ($placestore->places as $place) {
                // Prevent infinite loop.
                if ($place->linkedActivity == $this->cm->id) {
                    continue;
                }
                // Skip non-target places when there is no condition to visit all places.
                if ($map->completiontype != self::COMPLETION_WITH_ALL_PLACES && !in_array($place->id, $placestore->targetplaces)) {
                    continue;
                }
                if ($place->linkedActivity != null) {
                    if (in_array($place->linkedActivity, $allcms)) {
                        $placecm = $modinfo->get_cm($place->linkedActivity);
                    } else {
                        // No way to fulfill condition.
                        if ($map->completiontype > self::COMPLETION_WITH_ONE_TARGET) {
                            return COMPLETION_INCOMPLETE;
                        }
                        $placecm = false;
                    }

                    if (
                        !$placecm ||
                        !$this->activitymanager->is_completed($placecm)
                    ) {
                        // No way to fulfill condition.
                        if ($map->completiontype > self::COMPLETION_WITH_ONE_TARGET) {
                            return COMPLETION_INCOMPLETE;
                        }
                    } else {
                        // We need only one.
                        if (
                            $map->completiontype == self::COMPLETION_WITH_ONE_TARGET &&
                            $this->activitymanager->is_completed($placecm)
                        ) {
                            return COMPLETION_COMPLETE;
                        }
                    }
                } else {
                    // No way to fulfill condition.
                    if ($map->completiontype > self::COMPLETION_WITH_ONE_TARGET) {
                        return COMPLETION_INCOMPLETE;
                    }
                }
            }
            if ($map->completiontype == self::COMPLETION_WITH_ONE_TARGET) {
                return COMPLETION_INCOMPLETE;
            } else {
                return COMPLETION_COMPLETE;
            }
        }
        return COMPLETION_INCOMPLETE;
    }

    /**
     * Defines the names of custom completion rules.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'completion_with_one_target',
            'completion_with_all_targets',
            'completion_with_all_places',
        ];
    }

    /**
     * Returns the descriptions of the custom completion rules
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completion_with_one_target' => get_string('completiondetail:one_target', 'learningmap'),
            'completion_with_all_targets' => get_string('completiondetail:all_targets', 'learningmap'),
            'completion_with_all_places' => get_string('completiondetail:all_places', 'learningmap'),
        ];
    }

    /**
     * Returns the sort order of completion rules
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completion_with_one_target',
            'completion_with_all_targets',
            'completion_with_all_places',
        ];
    }

    /**
     * Show the manual completion or not regardless of the course's showcompletionconditions setting.
     *
     * @return bool
     */
    public function manual_completion_always_shown(): bool {
        return true;
    }
}
