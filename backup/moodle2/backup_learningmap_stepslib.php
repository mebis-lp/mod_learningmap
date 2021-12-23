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

defined('MOODLE_INTERNAL') || die();

/**
 * Backup steps for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright   2021, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     https://www.gnu.org/licenses/agpl-3.0.html GNU AGPL v3 or later
 */
class backup_learningmap_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the XML structure for learningmap backups
     *
     * @return backup_nested_element
     */
    protected function define_structure() : backup_nested_element {
        $learningmap = new backup_nested_element(
            'learningmap',
            ['id'],
            ['course', 'name', 'intro', 'introformat', 'timemodified', 'placestore', 'completiontype']
        );

        $learningmap->set_source_table('learningmap', ['id' => backup::VAR_ACTIVITYID]);

        $learningmap->annotate_files('mod_learningmap', 'intro', null);

        return $this->prepare_activity_structure($learningmap);
    }
}
