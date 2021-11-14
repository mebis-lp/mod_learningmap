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
 * Restore class for mod_learningmap
 *
 * @package mod_learningmap
 * @copyright  2021 Stefan Hanauska <stefan.hanauska@altmuehlnet.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot.'/mod/learningmap/backup/moodle2/restore_learningmap_stepslib.php');

class restore_learningmap_activity_task extends restore_activity_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new restore_learningmap_activity_structure_step('learningmap_structure', 'learningmap.xml'));
    }

    // Defining this to make SVG hacks possible.
    public static function define_decode_contents() {
        $contents = array();
        $contents[] = new restore_decode_content('learningmap', array('intro'), 'learningmap');
        return $contents;
    }

    public static function define_decode_rules() {
        $rules = array();
        $rules[] = new restore_decode_rule('LEARNINGMAPVIEWBYID', '/mod/learningmap/view.php?id=$1', 'course_module');
        return $rules;
    }

    /**
     * Update placestore to new module ids after restore is complete
     * @throws dml_exception
     */
    public function after_restore() {
        global $DB;

        $item = $DB->get_record('learningmap', array('id' => $this->get_activityid()), '*', MUST_EXIST);

        $placestore = json_decode($item->placestore);

        foreach ($placestore->places as $place) {
            if ($place->linkedActivity) {
                $moduleid = restore_dbops::get_backup_ids_record($this->get_restoreid(), 'course_module', $place->linkedActivity);
                if ($moduleid) {
                    $place->linkedActivity = $moduleid['newitemid'];
                } else {
                    $place->linkedActivity = null;
                }
            }
        }

        $item->placestore = json_encode($placestore);

        $DB->update_record('learningmap', $item);

    }
}
