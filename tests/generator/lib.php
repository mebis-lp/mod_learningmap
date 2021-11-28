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
 * mod_learningmap data generator
 *
 * @package mod_learningmap
 * @copyright  2021 Stefan Hanauska <stefan.hanauska@altmuehlnet.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class mod_learningmap_generator extends testing_module_generator {

    public function create_instance($record = null, array $options = null) {
        global $CFG;

        $record = (array)$record + array(
            'name' => 'test map',
            'intro' => file_get_contents('./test.svg'),
            'introformat' => 1,
            'placestore' => file_get_contents('./test.json'),
            'completiontype' => 2
        );

        return parent::create_instance($record, (array)$options);
    }
}
