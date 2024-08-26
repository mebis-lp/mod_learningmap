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
 * This class provides helper functions for migration from learningmaps being stored in the intro field to the new column svgcode.
 *
 * @package    mod_learningmap
 * @copyright  2024 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migrationhelper {
    /**
     * Moves all files from the intro field to the new filearea 'background'.
     * This is only executed if there are no files in the background filearea yet.
     * @param int $instanceid The id of the learningmap instance.
     */
    public static function move_files_to_background_filearea(int $instanceid) {
        global $DB;
        $fs = get_file_storage();

        // Getting course modules via DB query as get_coursemodule_from_instance() is not available during upgrade.
        $moduleid = $DB->get_field('modules', 'id', ['name' => 'learningmap']);

        if (empty($moduleid)) {
            return;
        }

        $cm = $DB->get_record('course_modules', ['instance' => $instanceid, 'module' => $moduleid]);

        $contextid = \context_module::instance($cm->id)->id;

        // Check if there are already files in the background filearea.
        $backgroundfiles = $fs->get_area_files($contextid, 'mod_learningmap', 'background', 0, 'id', false);
        if (count($backgroundfiles) != 0) {
            return;
        }

        $files = $fs->get_area_files($contextid, 'mod_learningmap', 'intro', 0, 'id', false);
        foreach ($files as $file) {
            $filerecord = [
                'contextid'    => $file->get_contextid(),
                'component'    => 'mod_learningmap',
                'filearea'     => 'background',
                'itemid'       => 0,
                'filepath'     => '/',
                'filename'     => $file->get_filename(),
                'timecreated'  => time(),
                'timemodified' => time(),
              ];
            $fs->create_file_from_storedfile($filerecord, $file);
            $file->delete();
        }
    }

    /**
     * Checks if the given learningmap instance has a SVG code stored in the 'svgcode' column.
     *
     * @param int $instanceid The id of the learningmap instance.
     * @return bool
     */
    public static function is_version_without_svgcode(int $instanceid): bool {
        global $DB;
        $entry = $DB->get_record('learningmap', ['id' => $instanceid]);
        return empty($entry->svgcode);
    }

    /**
     * Update one learningmap instance to have the SVG code stored in the 'svgcode' column.
     *
     * @param int $instanceid The id of the learningmap instance. If empty, all learningmaps will be updated.
     * @return void
     */
    public static function update_learningmaps_to_use_svgcode(int $instanceid = 0) {
        global $DB;

        // Getting course modules via DB query as get_coursemodule_from_instance() is not available during upgrade.
        $moduleid = $DB->get_field('modules', 'id', ['name' => 'learningmap']);

        if (empty($moduleid)) {
            return;
        }

        $params = [];
        $where = 'svgcode IS NULL';
        if (!empty($instanceid)) {
            $where .= ' AND id = :id';
            $params['id'] = $instanceid;
        }

        $learningmaps = $DB->get_recordset_select('learningmap', $where, $params);
        foreach ($learningmaps as $learningmap) {
            $cm = $DB->get_record('course_modules', ['instance' => $learningmap->id, 'module' => $moduleid]);
            $learningmap->svgcode = $learningmap->intro;
            $learningmap->intro = '';
            // To keep a consistent behavior with the old version, we set the showmaponcoursepage to the value of
            // showdescription (which was responsible for displaying the map on course page before).
            $learningmap->showmaponcoursepage = $cm->showdescription;
            $DB->update_record('learningmap', $learningmap);
            self::move_files_to_background_filearea($learningmap->id);
        }
        $learningmaps->close();
    }
}
