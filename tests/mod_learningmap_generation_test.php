<?php
// mod_learningmap - A moodle plugin for easy visualization of learning paths
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Unit test for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright   2021-2022, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     https://www.gnu.org/licenses/agpl-3.0.html GNU AGPL v3 or later
 * @group      mod_learningmap
 * @group      mebis
 */
class mod_learningmap_generation_test extends advanced_testcase {
    /**
     * Tests the data generator for this module
     *
     * @return void
     */
    public function test_create_instance() : void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $this->assertFalse($DB->record_exists('learningmap', array('course' => $course->id)));
        $learningmap = $this->getDataGenerator()->create_module('learningmap', ['course' => $course]);

        $records = $DB->get_records('learningmap', ['course' => $course->id], 'id');
        $this->assertCount(1, $records);
        $this->assertTrue(array_key_exists($learningmap->id, $records));
    }
}
