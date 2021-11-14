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

/**
 * Unit test for mod_learningmap
 *
 * @package mod_learningmap
 * @copyright  2021 Stefan Hanauska <stefan.hanauska@altmuehlnet.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group mod_learningmap
 * @group mebis
 */

class mod_learningmap_generation_testcase extends advanced_testcase {
    public function test_create_instance() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $this->assertFalse($DB->record_exists('learningmap', array('course' => $course->id)));
        $learningmap = $this->getDataGenerator()->create_module('learningmap', array('course' => $course));

        $records = $DB->get_records('learningmap', array('course' => $course->id), 'id');
        $this->assertCount(1, $records);
        $this->assertTrue(array_key_exists($learningmap->id, $records));
    }
}
