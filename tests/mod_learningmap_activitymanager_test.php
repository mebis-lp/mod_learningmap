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

use course_modinfo;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/learningmap/tests/mod_learningmap_testcase.php');

/**
 * Unit test for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright   2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      mod_learningmap
 * @group      mebis
 * @covers     \mod_learningmap\activitymanager
 */
final class mod_learningmap_activitymanager_test extends mod_learningmap_testcase {
    /**
     * Activitymanager instance for testing.
     * @var activitymanager $activitymanager
     */
    protected $activitymanager;

    /**
     * Test completion checking
     * @return void
     */
    public function test_is_completed(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare(completion\custom_completion::COMPLETION_WITH_ONE_TARGET, true, true);
        $this->activitymanager = new activitymanager($this->course, $this->users[0]);
        $cm = $this->modinfo->get_cm($this->activities[0]->cmid);
        $this->completion->set_module_viewed($cm, $this->users[0]->id);
        // The module requires only viewing it. Should be complete now.
        $this->assertEquals($this->activitymanager->is_completed($cm), true);

        $assign = $this->activities[7];
        $grades = [];
        $grades[$this->users[0]->id] = (object)[
            'rawgrade' => 90, 'userid' => $this->users[0]->id,
        ];
        $assign->cmidnumber = null;
        assign_grade_item_update($assign, $grades);
        $cm = $this->modinfo->get_cm($this->activities[7]->cmid);
        // The module requires only viewing it. Should be incomplete after grading, as it is not viewed yet.
        $this->assertEquals($this->activitymanager->is_completed($cm), false);
        $this->completion->set_module_viewed($cm, $this->users[0]->id);
        // Should be complete after viewing it.
        $this->assertEquals($this->activitymanager->is_completed($cm), true);

        $assign = $this->activities[8];
        $grades = [];
        $grades[$this->users[0]->id] = (object)[
            'rawgrade' => 1, 'userid' => $this->users[0]->id,
        ];
        $assign->cmidnumber = $assign->cmid;
        assign_grade_item_update($assign, $grades);
        $cm = $this->modinfo->get_cm($this->activities[8]->cmid);
        // The module requires viewing it and a passing grade. Should be incomplete after grading, as it is not viewed yet
        // and it the grade is below the passing grade.
        $this->assertEquals($this->activitymanager->is_completed($cm), false);
        $this->completion->set_module_viewed($cm, $this->users[0]->id);
        // Should still be incomplete, as passing grade is not reached.
        $this->assertEquals($this->activitymanager->is_completed($cm), false);
        $grades[$this->users[0]->id] = (object)[
            'rawgrade' => 90, 'userid' => $this->users[0]->id,
        ];
        $result = assign_grade_item_update($assign, $grades);
        $this->assertEquals(0, $result);
        // Should be complete as passing grade is reached now.
        $this->assertEquals($this->activitymanager->is_completed($cm), true);

        $this->activitymanager = new activitymanager($this->course, $this->users[1], $this->group->id);
        $cm = $this->modinfo->get_cm($this->activities[0]->cmid);
        $this->assertEquals($this->activitymanager->is_completed($cm), true);
        $cm = $this->modinfo->get_cm($this->activities[1]->cmid);
        $this->assertEquals($this->activitymanager->is_completed($cm), false);
        $cm = $this->modinfo->get_cm($this->activities[7]->cmid);
        $this->assertEquals($this->activitymanager->is_completed($cm), true);
        $cm = $this->modinfo->get_cm($this->activities[8]->cmid);
        $this->assertEquals($this->activitymanager->is_completed($cm), true);
    }
}
