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
 * Class for handling the activities of a learning map
 *
 * @package     mod_learningmap
 * @copyright   2021-2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activities {
    /**
     * User object for completion
     * @var core_user
     */
    protected $user;
    /**
     * Stores the group id when using group mode. 0 if no group is used.
     * @var int
     */
    protected $group;
    /**
     * Course object for completion
     * @var stdClass
     */
    protected $course;
    /**
     * Completion information for course
     * @var completion_info
     */
    protected $completion;

    /**
     * Creates activities helper
     * @param stdClass $course Course object for completion
     * @param stdClass $user User object
     * @param int $group Group id (0 by default, means no group)
     */
    public function __construct(stdClass $course, stdClass $user, int $group = 0) {
        $this->user = $user;
        $this->group = $group;
        $this->course = $course;
        $this->completion = new completion_info($course);
    }

    /**
     * Checks whether a given course module is completed (either by the user or at least one
     * of the users of the group, if group is set).
     *
     * @param \cm_info $cm course module to check
     */
    public function is_completed(cm_info $cm): bool {
        if (!empty($this->group)) {
            $members = groups_get_members($this->group);
        }
        if (empty($members)) {
            $members = [$this->user];
        }
        foreach ($members as $member) {
            if ($this->completion->get_data($cm, true, $member->id)->completionstate == COMPLETION_COMPLETE ||
                $this->completion->get_data($cm, true, $member->id)->completionstate == COMPLETION_COMPLETE_PASS) {
                return true;
            }
        }
        return false;
    }
}
