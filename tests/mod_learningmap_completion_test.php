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
        $this->learningmap = $this->getDataGenerator()->create_module('learningmap',
            ['course' => $this->course, 'completion' => 2, 'completiontype' => $completiontype,
            'groupmode' => ($groupmode ? SEPARATEGROUPS : NOGROUPS)]
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
                'email' => 'users[0]@example.com',
                'username' => 'users[0]'
            ]
        );

        if ($this->groupmode) {
            $this->group = $this->getDataGenerator()->create_group(['courseid' => $this->course->id]);
            $this->users[1] = $this->getDataGenerator()->create_user(
                [
                    'email' => 'users[1]@example.com',
                    'username' => 'users[1]'
                ]
            );
            $this->users[2] = $this->getDataGenerator()->create_user(
                [
                    'email' => 'users[2]@example.com',
                    'username' => 'users[2]'
                ]
            );
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
     * Tests all completiontypes in individual and group mode
     *
     * @return void
     */
    public function test_completion() : void {
        // Individual mode
        $this->run_completion_test(LEARNINGMAP_COMPLETION_WITH_ONE_TARGET, false, 7);
        $this->run_completion_test(LEARNINGMAP_COMPLETION_WITH_ALL_TARGETS, false, 8);
        $this->run_completion_test(LEARNINGMAP_COMPLETION_WITH_ALL_PLACES, false, 8);
        // Group mode
        $this->run_completion_test(LEARNINGMAP_COMPLETION_WITH_ONE_TARGET, true, 7);
        $this->run_completion_test(LEARNINGMAP_COMPLETION_WITH_ALL_TARGETS, true, 8);
        $this->run_completion_test(LEARNINGMAP_COMPLETION_WITH_ALL_PLACES, true, 8);
    }

    /**
     * Tests completion by reaching all places in group mode
     *
     * @return void
     */
    public function run_completion_test(int $type, bool $groupmode, int $completedfrom) : void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare($type, $groupmode);
        for ($j = 0; $j < ($groupmode ? 3 : 1); $j++) {
            $this->assertEquals(
                COMPLETION_INCOMPLETE,
                $this->completion->get_data($this->cm, true, $this->users[$j]->id)->completionstate
            );
        }

        for ($i = 0; $i < 9; $i++) {
            $acm = $this->modinfo->get_cm($this->activities[$i]->cmid);
            $this->completion->set_module_viewed($acm, $this->users[0]->id);
            for ($j = 0; $j < ($groupmode ? 3 : 1); $j++) {
                $this->completion->update_state($this->cm, COMPLETION_UNKNOWN, $this->users[$j]->id);
            }
            if ($i < $completedfrom) {
                for ($j = 0; $j < ($groupmode ? 3 : 1); $j++) {
                    $this->assertEquals(
                        COMPLETION_INCOMPLETE,
                        $this->completion->get_data($this->cm, true, $this->users[$j]->id)->completionstate
                    );
                }
            } else {
                for ($j = 0; $j < ($groupmode ? 2 : 1); $j++) {
                    $this->assertEquals(
                        COMPLETION_INCOMPLETE,
                        $this->completion->get_data($this->cm, true, $this->users[$j]->id)->completionstate
                    );
                }
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->users[2]->id)->completionstate
                );
            }

        }
    }
}
