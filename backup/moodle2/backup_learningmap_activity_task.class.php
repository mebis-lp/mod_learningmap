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
global $CFG;
require_once($CFG->dirroot.'/mod/learningmap/backup/moodle2/backup_learningmap_stepslib.php');

/**
 * Backup class for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright   2021-2022, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     https://www.gnu.org/licenses/agpl-3.0.html GNU AGPL v3 or later
 */
class backup_learningmap_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() : void {
    }

    /**
     * Defines a backup step to store the instance data in the learningmap.xml file
     */
    protected function define_my_steps() : void {
        $this->add_step(new backup_learningmap_activity_structure_step('learningmap_structure', 'learningmap.xml'));
    }

    /**
     * Encodes the links to view.php for backup
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) : string {
        global $CFG;

        $base = preg_quote($CFG->wwwroot.'/mod/learningmap', '#');

        $pattern = "#(".$base."\/view.php\?id\=)([0-9]+)#";
        $content = preg_replace($pattern, '$@LEARNINGMAPVIEWBYID*$2@$', $content);
        return $content;
    }
}
