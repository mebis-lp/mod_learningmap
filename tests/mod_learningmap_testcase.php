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

namespace mod_learningmap;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../lib.php');

/**
 * Test class with common test methods for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright 2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mod_learningmap_testcase extends \advanced_testcase {
    /**
     * The course used for testing
     * @var \stdClass
     */
    protected $course;

    /**
     * The learning map used for testing
     * @var \stdClass
     */
    protected $learningmap;

    /**
     * The activities linked in the learningmap
     * @var array
     */
    protected $activities;

    /**
     * The users used for testing
     * @var array
     */
    protected $users;

    /**
     * The group used for testing
     * @var \stdClass
     */
    protected $group;

    /**
     * Whether group mode is active
     * @var bool
     */
    protected $groupmode;

    /**
     * The modinfo of the course
     * @var \course_modinfo|null
     */
    protected $modinfo;

    /**
     * The completion info of the course
     * @var \completion_info
     */
    protected $completion;

    /**
     * The cm_info object belonging to the learning map (differs from the learningmap record)
     * @var \cm_info
     */
    protected $cm;

    /**
     * Prepare testing environment
     * @param int $completiontype Type for automatic completion
     * @param bool $groupmode Whether to use group mode (defaults to false)
     * @param bool $passinggrade Whether to use activities with passing grade
     */
    public function prepare($completiontype, $groupmode = false, $passinggrade = false): void {
        global $DB;
        $this->groupmode = $groupmode;
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        // Set up the learning map for this test.
        $this->learningmap = $this->getDataGenerator()->create_module(
            'learningmap',
            ['course' => $this->course, 'completion' => 2, 'completiontype' => $completiontype,
            'groupmode' => ($groupmode ? SEPARATEGROUPS : NOGROUPS), ]
        );

        $this->activities = [];
        // Create activities for this test. If we test with passing grade, the last two activities
        // will have a passing grade set.
        for ($i = 0; $i < ($passinggrade ? 7 : 9); $i++) {
            $this->activities[] = $this->getDataGenerator()->create_module(
                'page',
                [
                    'name' => 'A',
                    'content' => 'B',
                    'course' => $this->course,
                    'completion' => COMPLETION_TRACKING_AUTOMATIC,
                    'completionview' => COMPLETION_VIEW_REQUIRED,
                ]
            );
            $this->learningmap->placestore = str_replace(99990 + $i, $this->activities[$i]->cmid, $this->learningmap->placestore);
        }
        if ($passinggrade) {
            $assignrow = $this->getDataGenerator()->create_module('assign', [
                'course' => $this->course->id,
                'name' => 'Assign without passinggrade completion',
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionview' => COMPLETION_VIEW_REQUIRED,
                'gradefeedbackenabled' => true,
            ]);
            $assign = new \assign(\context_module::instance($assignrow->cmid), false, false);
            $gradeitem = $assign->get_grade_item();
            \grade_object::set_properties($gradeitem, ['gradepass' => 50.0]);
            $gradeitem->update();
            $this->activities[] = $assignrow;
            $this->learningmap->placestore = str_replace(99997, $assignrow->cmid, $this->learningmap->placestore);
            $assignrow = $this->getDataGenerator()->create_module('assign', [
                'course' => $this->course->id,
                'name' => 'Assign with passinggrade completion',
                'completion' => COMPLETION_TRACKING_AUTOMATIC,
                'completionpassgrade' => true,
                'completionusegrade' => true,
                'completionview' => COMPLETION_VIEW_NOT_REQUIRED,
                'gradefeedbackenabled' => true,
            ]);
            $DB->set_field('course_modules', 'completiongradeitemnumber', 0, ['id' => $assignrow->cmid]);
            rebuild_course_cache($this->course->id, true);
            $assign = new \assign(\context_module::instance($assignrow->cmid), false, false);
            $gradeitem = $assign->get_grade_item();
            \grade_object::set_properties($gradeitem, ['gradepass' => 50.0]);
            $gradeitem->update();
            $this->activities[] = $assignrow;
            $this->learningmap->placestore = str_replace(99998, $assignrow->cmid, $this->learningmap->placestore);
        }
        $DB->set_field('learningmap', 'placestore', $this->learningmap->placestore, ['id' => $this->learningmap->id]);

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        $this->users[0] = $this->getDataGenerator()->create_user(
            [
                'email' => 'user1@example.com',
                'username' => 'user1',
            ]
        );
        $this->getDataGenerator()->enrol_user($this->users[0]->id, $this->course->id, $studentrole->id);

        if ($this->groupmode) {
            $this->group = $this->getDataGenerator()->create_group(['courseid' => $this->course->id]);
            $this->users[1] = $this->getDataGenerator()->create_user(
                [
                    'email' => 'user2@example.com',
                    'username' => 'user2',
                ]
            );
            $this->users[2] = $this->getDataGenerator()->create_user(
                [
                    'email' => 'user3@example.com',
                    'username' => 'user3',
                ]
            );
            $this->getDataGenerator()->enrol_user($this->users[1]->id, $this->course->id, $studentrole->id);
            $this->getDataGenerator()->enrol_user($this->users[2]->id, $this->course->id, $studentrole->id);
            $this->getDataGenerator()->create_group_member([
                'userid' => $this->users[0]->id,
                'groupid' => $this->group->id,
            ]);
            $this->getDataGenerator()->create_group_member([
                'userid' => $this->users[1]->id,
                'groupid' => $this->group->id,
            ]);
        }

        $this->modinfo = get_fast_modinfo($this->course, $this->users[0]->id);
        $this->completion = new \completion_info($this->modinfo->get_course());
        $this->cm = $this->modinfo->get_cm($this->learningmap->cmid);
    }
}
