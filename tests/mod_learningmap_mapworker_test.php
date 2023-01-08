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
 * @covers     \mod_learningmap\mapworker
 */
class mod_learningmap_mapworker_test extends \advanced_testcase {

    /**
     * Prepare testing environment
     */
    public function prepare(): void {
        global $DB;
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->learningmap = $this->getDataGenerator()->create_module('learningmap',
            ['course' => $this->course, 'completion' => 2, 'completiontype' => 0]);

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

        $this->modinfo = get_fast_modinfo($this->course, $this->user1->id);
        $this->completion = new \completion_info($this->modinfo->get_course());
        $this->cm = $this->modinfo->get_cm($this->learningmap->cmid);
    }

    /**
     * Tests slicemode
     *
     * @return void
     */
    public function test_slicemode() : void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->prepare();
        $this->setUser($this->user1);
        $placestore = json_decode($this->learningmap->placestore, true);
        $placestore['slicemode'] = true;
        $mapworker = new mapworker($this->learningmap->intro, $placestore, $this->cm, false);
        $mapworker->process_map_objects();
        $expectedvalues = [
            '72 47 266 61',
            '72 47 266 195',
            '72 47 409 302',
            '72 47 409 302',
            '72 47 409 302',
            '72 47 409 302',
            '72 47 577 302',
            '0 0 800 2111',
            '0 0 800 2111',
        ];
        $viewbox = $mapworker->getattribute('learningmap-svgmap-63bb1c8edb6af', 'viewBox');
        $this->assertEquals($viewbox, '37 12 100 100');

        for ($i = 0; $i < 9; $i++) {
            $acm = $this->modinfo->get_cm($this->activities[$i]->cmid);
            $this->completion->set_module_viewed($acm, $this->user1->id);
            $mapworker = new mapworker($this->learningmap->intro, $placestore, $this->cm, false);
            $mapworker->process_map_objects();
            $viewbox = $mapworker->getattribute('learningmap-svgmap-63bb1c8edb6af', 'viewBox');
            $this->assertEquals($viewbox, $expectedvalues[$i]);
        }
    }
}
