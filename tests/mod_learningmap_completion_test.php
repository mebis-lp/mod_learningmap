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
use mod_learningmap\completion\custom_completion;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../lib.php');

/**
 * Unit test for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright   2021-2022, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      mod_learningmap
 * @group      mebis
 * @covers     \mod_learningmap\completion\custom_completion
 */
class mod_learningmap_completion_test extends \advanced_testcase {
    /**
     * The course used for testing
     *
     * @var \stdClass
     */
    protected $course;
    /**
     * The learning map used for testing
     *
     * @var \stdClass
     */
    protected $learningmap;
    /**
     * The activities linked in the learningmap
     *
     * @var array
     */
    protected $activities;
    /**
     * The users used for testing
     *
     * @var array
     */
    protected $users;
    /**
     * The group used for testing
     *
     * @var \stdClass
     */
    protected $group;
    /**
     * Whether group mode is active
     *
     * @var boolean
     */
    protected $groupmode;
    /**
     * The modinfo of the course
     *
     * @var \course_modinfo|null
     */
    protected $modinfo;
    /**
     * The completion info of the course
     *
     * @var \completion_info
     */
    protected $completion;
    /**
     * The cm_info object belonging to the learning map (differs from the learningmap record)
     *
     * @var \cm_info
     */
    protected $cm;
    /**
     * Prepare testing environment
     */
    /**
     * Prepare testing environment
     * @param int $completiontype Type for automatic completion
     * @param bool $groupmode Whether to use group mode (defaults to false)
     */
    public function prepare($completiontype, $groupmode = false): void {
        global $DB;
        $this->groupmode = $groupmode;
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->learningmap = $this->getDataGenerator()->create_module(
            'learningmap',
            ['course' => $this->course, 'completion' => 2, 'completiontype' => $completiontype,
            'groupmode' => ($groupmode ? SEPARATEGROUPS : NOGROUPS), ]
        );

        $this->activities = [];
        for ($i = 0; $i < 9; $i++) {
            $this->activities[] = $this->getDataGenerator()->create_module(
                'page',
                ['name' => 'A', 'content' => 'B', 'course' => $this->course, 'completion' => 2, 'completionview' => 1]
            );
            $this->learningmap->placestore = str_replace(99990 + $i, $this->activities[$i]->cmid, $this->learningmap->placestore);
        }
        $DB->set_field('learningmap', 'placestore', $this->learningmap->placestore, ['id' => $this->learningmap->id]);

        $this->users[0] = $this->getDataGenerator()->create_user(
            [
                'email' => 'user1@example.com',
                'username' => 'user1',
            ]
        );
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
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

    /**
     * Tests completiontype 1 in individual mode
     *
     * @return void
     */
    public function test_completiontype1_individual(): void {
        $this->run_completion_test(custom_completion::COMPLETION_WITH_ONE_TARGET, false, 7);
    }

    /**
     * Tests completiontype 2 in individual mode
     *
     * @return void
     */
    public function test_completiontype2_individual(): void {
        $this->run_completion_test(custom_completion::COMPLETION_WITH_ALL_TARGETS, false, 8);
    }

    /**
     * Tests completiontype 3 in individual mode
     *
     * @return void
     */
    public function test_completiontype3_individual(): void {
        $this->run_completion_test(custom_completion::COMPLETION_WITH_ALL_PLACES, false, 8);
    }

    /**
     * Tests completiontype 1 in group mode
     *
     * @return void
     */
    public function test_completiontype1_group(): void {
        $this->run_completion_test(custom_completion::COMPLETION_WITH_ONE_TARGET, true, 7);
    }

    /**
     * Tests completiontype 2 in group mode
     *
     * @return void
     */
    public function test_completiontype2_group(): void {
        $this->run_completion_test(custom_completion::COMPLETION_WITH_ALL_TARGETS, true, 8);
    }

    /**
     * Tests completiontype 3 in group mode
     *
     * @return void
     */
    public function test_completiontype3_group(): void {
        $this->run_completion_test(custom_completion::COMPLETION_WITH_ALL_PLACES, true, 8);
    }

    /**
     * Run tests for completion.
     *
     * @param int $type Completion type
     * @param bool $groupmode Whether to run in group mode
     * @param int $completedfrom Number of the activity to expect completion
     * @return void
     */
    public function run_completion_test(int $type, bool $groupmode, int $completedfrom): void {
        global $SESSION;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare($type, $groupmode);
        for ($j = 0; $j < ($groupmode ? 3 : 1); $j++) {
            $this->setUser($this->users[$j]);
            if ($groupmode) {
                $SESSION->activegroup[$this->course->id][SEPARATEGROUPS][0] = ($j < 2 ? $this->group->id : 0);
            }
            $this->assertEquals(
                COMPLETION_INCOMPLETE,
                $this->completion->get_data($this->cm, true, $this->users[$j]->id)->completionstate
            );
        }

        for ($i = 0; $i < 9; $i++) {
            $acm = $this->modinfo->get_cm($this->activities[$i]->cmid);
            $this->setUser($this->users[0]);
            if ($groupmode) {
                $SESSION->activegroup[$this->course->id][SEPARATEGROUPS][0] = $this->group->id;
            }
            $this->completion->set_module_viewed($acm, $this->users[0]->id);
            for ($j = 0; $j < ($groupmode ? 3 : 1); $j++) {
                $this->setUser($this->users[$j]);
                if ($groupmode) {
                    $SESSION->activegroup[$this->course->id][SEPARATEGROUPS][0] = ($j < 2 ? $this->group->id : 0);
                }
                $this->completion->update_state($this->cm, COMPLETION_UNKNOWN, $this->users[$j]->id);
            }
            if ($i < $completedfrom) {
                for ($j = 0; $j < ($groupmode ? 3 : 1); $j++) {
                    $this->setUser($this->users[$j]);
                    if ($groupmode) {
                        $SESSION->activegroup[$this->course->id][SEPARATEGROUPS][0] = ($j < 2 ? $this->group->id : 0);
                    }
                    $this->assertEquals(
                        COMPLETION_INCOMPLETE,
                        $this->completion->get_data($this->cm, true, $this->users[$j]->id)->completionstate
                    );
                }
            } else {
                for ($j = 0; $j < ($groupmode ? 2 : 1); $j++) {
                    $this->setUser($this->users[$j]);
                    if ($groupmode) {
                        $SESSION->activegroup[$this->course->id][SEPARATEGROUPS][0] = $this->group->id;
                    }
                    $this->assertEquals(
                        COMPLETION_COMPLETE,
                        $this->completion->get_data($this->cm, true, $this->users[$j]->id)->completionstate,
                        'Completion not set for user ' . $j
                    );
                }
                if ($groupmode) {
                    $this->setUser($this->users[2]);
                    $SESSION->activegroup[$this->course->id][SEPARATEGROUPS][0] = 0;
                    $this->assertEquals(
                        COMPLETION_INCOMPLETE,
                        $this->completion->get_data($this->cm, true, $this->users[2]->id)->completionstate
                    );
                }
            }
        }
    }
}
