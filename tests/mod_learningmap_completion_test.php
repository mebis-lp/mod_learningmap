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
     * Prepare testing environment
     * @param int $completiontype Type for automatic completion
     * @param bool $groupmode Whether to use group mode (defaults to false)
     */
    public function prepare($completiontype, $groupmode = false): void {
        global $DB;
        $this->groupmode = $groupmode;
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->learningmap = $this->getDataGenerator()->create_module('learningmap',
            ['course' => $this->course, 'completion' => 2, 'completiontype' => $completiontype]);

        $this->activities = [];
        for ($i = 0; $i < 9; $i++) {
            $this->activities[] = $this->getDataGenerator()->create_module(
                'page',
                ['name' => 'A', 'content' => 'B', 'course' => $this->course, 'completion' => 2, 'completionview' => 1]
            );
            $this->learningmap->placestore = str_replace(99990 + $i, $this->activities[$i]->cmid, $this->learningmap->placestore);
        }
        $DB->set_field('learningmap', 'placestore', $this->learningmap->placestore, ['id' => $this->learningmap->id]);

        $this->user1 = $this->getDataGenerator()->create_user(
            [
                'email' => 'user1@example.com',
                'username' => 'user1'
            ]
        );

        if ($this->groupmode) {
            $this->group = $this->getDataGenerator()->create_group(['courseid' => $this->course->id]);
            $this->user2 = $this->getDataGenerator()->create_user(
                [
                    'email' => 'user2@example.com',
                    'username' => 'user2'
                ]
            );
            $this->user3 = $this->getDataGenerator()->create_user(
                [
                    'email' => 'user3@example.com',
                    'username' => 'user3'
                ]
            );
            $this->getDataGenerator()->create_group_member([
                'userid' => $this->user1->id,
                'groupid' => $this->group->id,
            ]);
            $this->getDataGenerator()->create_group_member([
                'userid' => $this->user2->id,
                'groupid' => $this->group->id,
            ]);
        }

        $this->modinfo = get_fast_modinfo($this->course, $this->user1->id);
        $this->completion = new \completion_info($this->modinfo->get_course());
        $this->cm = $this->modinfo->get_cm($this->learningmap->cmid);
    }

    /**
     * Tests completion by reaching one target place
     *
     * @return void
     */
    public function test_completiontype1() : void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare(1);
        $this->assertEquals(
            COMPLETION_INCOMPLETE,
            $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
        );

        for ($i = 0; $i < 9; $i++) {
            $acm = $this->modinfo->get_cm($this->activities[$i]->cmid);
            $this->completion->set_module_viewed($acm, $this->user1->id);
            $this->completion->update_state($this->cm, COMPLETION_UNKNOWN, $this->user1->id);
            if ($i < 7) {
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
                );
            } else {
                $this->assertEquals(
                    COMPLETION_COMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
                );
            }
        }
    }

    /**
     * Tests completion by reaching all target places
     *
     * @return void
     */
    public function test_completiontype2() : void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare(2);
        $this->assertEquals(
            COMPLETION_INCOMPLETE,
            $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
        );

        for ($i = 0; $i < 9; $i++) {
            $acm = $this->modinfo->get_cm($this->activities[$i]->cmid);
            $this->completion->set_module_viewed($acm, $this->user1->id);
            $this->completion->update_state($this->cm, COMPLETION_UNKNOWN, $this->user1->id);
            if ($i < 8) {
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
                );
            } else {
                $this->assertEquals(
                    COMPLETION_COMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
                );
            }
        }
    }

    /**
     * Tests completion by reaching all places
     *
     * @return void
     */
    public function test_completiontype3() : void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare(3);
        $this->assertEquals(
            COMPLETION_INCOMPLETE,
            $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
        );

        for ($i = 0; $i < 9; $i++) {
            $acm = $this->modinfo->get_cm($this->activities[$i]->cmid);
            $this->completion->set_module_viewed($acm, $this->user1->id);
            $this->completion->update_state($this->cm, COMPLETION_UNKNOWN, $this->user1->id);
            if ($i < 8) {
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
                );
            } else {
                $this->assertEquals(
                    COMPLETION_COMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
                );
            }
        }
    }

    /**
     * Tests completion by reaching one target place
     *
     * @return void
     */
    public function test_completiontype1_group() : void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare(1, true);
        $this->assertEquals(
            COMPLETION_INCOMPLETE,
            $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
        );
        $this->assertEquals(
            COMPLETION_INCOMPLETE,
            $this->completion->get_data($this->cm, true, $this->user2->id)->completionstate
        );
        $this->assertEquals(
            COMPLETION_INCOMPLETE,
            $this->completion->get_data($this->cm, true, $this->user3->id)->completionstate
        );

        for ($i = 0; $i < 9; $i++) {
            $acm = $this->modinfo->get_cm($this->activities[$i]->cmid);
            $this->completion->set_module_viewed($acm, $this->user1->id);
            $this->completion->update_state($this->cm, COMPLETION_UNKNOWN, $this->user1->id);
            $this->completion->update_state($this->cm, COMPLETION_UNKNOWN, $this->user2->id);
            $this->completion->update_state($this->cm, COMPLETION_UNKNOWN, $this->user3->id);
            if ($i < 7) {
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
                );
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user2->id)->completionstate
                );
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user3->id)->completionstate
                );
            } else {
                $this->assertEquals(
                    COMPLETION_COMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
                );
                $this->assertEquals(
                    COMPLETION_COMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user2->id)->completionstate
                );
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user3->id)->completionstate
                );
            }
        }
    }

    /**
     * Tests completion by reaching all target places in group mode
     *
     * @return void
     */
    public function test_completiontype2_group() : void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare(2, true);
        $this->assertEquals(
            COMPLETION_INCOMPLETE,
            $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
        );

        for ($i = 0; $i < 9; $i++) {
            $acm = $this->modinfo->get_cm($this->activities[$i]->cmid);
            $this->completion->set_module_viewed($acm, $this->user1->id);
            $this->completion->update_state($this->cm, COMPLETION_UNKNOWN, $this->user1->id);
            $this->completion->update_state($this->cm, COMPLETION_UNKNOWN, $this->user2->id);
            $this->completion->update_state($this->cm, COMPLETION_UNKNOWN, $this->user3->id);
            if ($i < 8) {
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
                );
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user2->id)->completionstate
                );
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user3->id)->completionstate
                );
            } else {
                $this->assertEquals(
                    COMPLETION_COMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
                );
                $this->assertEquals(
                    COMPLETION_COMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user2->id)->completionstate
                );
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user3->id)->completionstate
                );
            }

        }
    }

    /**
     * Tests completion by reaching all places in group mode
     *
     * @return void
     */
    public function test_completiontype3_group() : void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare(3, true);
        $this->assertEquals(
            COMPLETION_INCOMPLETE,
            $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
        );

        for ($i = 0; $i < 9; $i++) {
            $acm = $this->modinfo->get_cm($this->activities[$i]->cmid);
            $this->completion->set_module_viewed($acm, $this->user1->id);
            $this->completion->update_state($this->cm, COMPLETION_UNKNOWN, $this->user1->id);
            $this->completion->update_state($this->cm, COMPLETION_UNKNOWN, $this->user2->id);
            $this->completion->update_state($this->cm, COMPLETION_UNKNOWN, $this->user3->id);
            if ($i < 8) {
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
                );
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user2->id)->completionstate
                );
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user3->id)->completionstate
                );
            } else {
                $this->assertEquals(
                    COMPLETION_COMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user1->id)->completionstate
                );
                $this->assertEquals(
                    COMPLETION_COMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user2->id)->completionstate
                );
                $this->assertEquals(
                    COMPLETION_INCOMPLETE,
                    $this->completion->get_data($this->cm, true, $this->user3->id)->completionstate
                );
            }

        }
    }
}
