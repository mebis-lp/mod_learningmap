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

namespace mod_learningmap;

use completion_info;
use cm_info;
use stdClass;

/**
 * Class for handling the activities used in a learning map.
 *
 * @package     mod_learningmap
 * @copyright 2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activitymanager {
    /**
     * User object for completion.
     * @var stdClass
     */
    protected stdClass $user;
    /**
     * Stores the group id when using group mode. 0 if no group is used.
     * @var int
     */
    protected int $group;
    /**
     * Course object for completion.
     * @var stdClass
     */
    protected stdClass $course;
    /**
     * Completion information for course.
     * @var completion_info
     */
    protected completion_info $completion;
    /**
     * Members of the group.
     * @var array
     */
    protected array $members;

    /**
     * Creates activitymanager helper.
     *
     * @param stdClass $course Course object for completion
     * @param stdClass $user User object
     * @param int $group Group id (0 by default, means no group)
     */
    public function __construct(stdClass $course, stdClass $user, int $group = 0) {
        $this->user = $user;
        $this->group = $group;
        $this->course = $course;
        $this->completion = new completion_info($course);
        if (!empty($this->group)) {
            $this->members = groups_get_members($this->group);
        }
        if (empty($this->members)) {
            $this->members = [$this->user];
        }
    }

    /**
     * Checks whether a given course module is completed (either by the user or at least one
     * of the users of the group, if group is set).
     * Please be aware: If an activity has a passing grade set, the passing grade is only used
     * to check completion when it is set as an completion requirement. Otherwise
     * @param \cm_info $cm course module to check
     */
    public function is_completed(cm_info $cm): bool {
        foreach ($this->members as $member) {
            $completionstate = $this->completion->get_data($cm, true, $member->id)->completionstate;
            if (
                $completionstate == COMPLETION_COMPLETE ||
                $completionstate == COMPLETION_COMPLETE_PASS ||
                (
                    intval($cm->completionpassgrade) == 0 &&
                    $completionstate == COMPLETION_COMPLETE_FAIL
                )
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the order of completion for the given array of course modules. Respects group mode.
     *
     * @param array $cms Course modules to check, array of objects with at least id attribute set or array of course module ids.
     * @return array Course module ids in order of completion
     */
    public function get_completion_order(array $cms): array {
        if (count($cms) > 0 && intval(current($cms)) > 0) {
            $intcms = $cms;
            $cms = array_map(function ($value) {
                $obj = new stdClass();
                $obj->id = $value;
                $obj->course = $this->course;
                return $obj;
            }, $intcms);
        }
        $completiontime = [];
        foreach ($cms as $cm) {
            foreach ($this->members as $member) {
                if (
                    $this->completion->get_data($cm, true, $member->id)->completionstate == COMPLETION_COMPLETE ||
                    $this->completion->get_data($cm, true, $member->id)->completionstate == COMPLETION_COMPLETE_PASS ||
                    (
                        intval($cm->completionpassgrade) == 0 &&
                        $this->completion->get_data($cm, true, $member->id)->completionstate == COMPLETION_COMPLETE_FAIL
                    )
                ) {
                    $completed = $this->completion->get_data($cm, true, $member->id)->timemodified;
                    if (!isset($completiontime[$cm->id]) || $completed < $completiontime[$cm->id]) {
                        $completiontime[$cm->id] = $completed;
                    }
                }
            }
        }
        asort($completiontime);
        return array_keys($completiontime);
    }
}
