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
    public function setUp(): void {
        global $DB;
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->learningmap = $this->getDataGenerator()->create_module('learningmap',
            ['course' => $this->course, 'completion' => 2, 'completiontype' => 0]);

        $this->modinfo = get_fast_modinfo($this->course, $this->user1->id);
        $this->completion = new \completion_info($this->modinfo->get_course());
        $this->cm = $this->modinfo->get_cm($this->learningmap->cmid);

        $this->activities = [];
        for ($i = 0; $i < 9; $i++) {
            $this->activities[] = $this->getDataGenerator()->create_module(
                'page',
                ['name' => 'A', 'content' => 'B', 'course' => $this->course, 'completion' => 2, 'completionview' => 1]
            );
            $this->learningmap->placestore = str_replace(99990 + $i, $this->activities[$i]->cmid, $this->learningmap->placestore);
            $this->completion->set_module_viewed($this->activities[$i], $this->user1->id);
        }
        $DB->set_field('learningmap', 'placestore', $this->learningmap->placestore, ['id' => $this->learningmap->id]);

        $this->user1 = $this->getDataGenerator()->create_user(
            [
                'email' => 'user1@example.com',
                'username' => 'user1'
            ]
        );

    }

    /**
     * Tests slicemode
     *
     * @return void
     */
    public function test_slicemode() : void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->setUser($this->user1);
        $placestore = json_decode($this->learningmap->placestore, true);
        $placestore['slicemode'] = true;
        $mapworker = new mapworker($this->learningmap->intro, $placestore, $this->cm, false);
        $mapworker->process_map_objects();
        $expectedvalues = [
            'M 0 0 L 0 2111 L 800 2111 L 800 0 Z M 72 47 L 338 47 L 338 108 L 72 108 Z',
            'M 0 0 L 0 2111 L 800 2111 L 800 0 Z M 72 47 L 338 47 L 338 242 L 72 242 Z',
            'M 0 0 L 0 2111 L 800 2111 L 800 0 Z M 72 47 L 481 47 L 481 349 L 72 349 Z',
            'M 0 0 L 0 2111 L 800 2111 L 800 0 Z M 72 47 L 481 47 L 481 349 L 72 349 Z',
            'M 0 0 L 0 2111 L 800 2111 L 800 0 Z M 72 47 L 481 47 L 481 349 L 72 349 Z',
            'M 0 0 L 0 2111 L 800 2111 L 800 0 Z M 72 47 L 481 47 L 481 349 L 72 349 Z',
            'M 0 0 L 0 2111 L 800 2111 L 800 0 Z M 72 47 L 649 47 L 649 349 L 72 349 Z',
            null
        ];
        $overlay = $mapworker->get_attribute('learningmap-overlay', 'd');
        $this->assertEquals($overlay, 'M 0 0 L 0 2111 L 800 2111 L 800 0 Z M 37 12 L 137 12 L 137 112 L 37 112 Z');

        for ($i = 0; $i < 8; $i++) {
            $acm = $this->modinfo->get_cm($this->activities[$i]->cmid);
            $this->completion->set_module_viewed($acm, $this->user1->id);
            $mapworker = new mapworker($this->learningmap->intro, $placestore, $this->cm, false);
            $mapworker->process_map_objects();
            $overlay = $mapworker->get_attribute('learningmap-overlay', 'd');
            $this->assertEquals($overlay, $expectedvalues[$i]);
        }
    }

    /**
     * Tests visibility dependent on activity completion
     *
     * @return void
     */
    public function test_visibility() : void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->setUser($this->user1);
        $placestore = json_decode($this->learningmap->placestore, true);
        $mapworker = new mapworker($this->learningmap->intro, $placestore, $this->cm, false);
        $mapworker->process_map_objects();
        // p0 is a starting place, so it should be visible by default.
        $this->assertEquals(['p0'], $mapworker->get_active());
        $expectedvalues = [
            ['p0', 'p1', 'p0_1'],
            ['p0', 'p1', 'p0_1', 'p4', 'p1_4'],
        ];

        for ($i = 0; $i < count($this->learningmap->placestore->places); $i++) {
            $acm = $this->modinfo->get_cm($this->placestore->places->linkedActivity);
            $this->completion->set_module_viewed($acm, $this->user1->id);
            $mapworker = new mapworker($this->learningmap->intro, $placestore, $this->cm, false);
            $mapworker->process_map_objects();
            $this->assertEquals($expectedvalues[$i], $mapworker->get_active());
        }
    }

}
