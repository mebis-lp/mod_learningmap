<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_learningmap;

/**
 * Cache manager class for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright   2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachemanager {
    /**
     * Resets and rebuilds the backlink cache for the whole instance.
     *
     * @return void
     */
    public static function rebuild_backlink_cache(): void {
        $cache = \cache::make('mod_learningmap', 'backlinks');
        $cache->purge();
        self::build_backlink_cache();
    }

    /**
     * Reset the backlink cache for a course (includes rebuilding it).
     *
     * @param int $courseid The id of the course.
     * @return void
     */
    public static function reset_backlink_cache(int $courseid): void {
        $cache = \cache::make('mod_learningmap', 'backlinks');
        $modinfo = get_fast_modinfo($courseid);
        $cms = $modinfo->get_cms();
        $cache->delete_many(array_keys($cms));
        self::build_backlink_cache($courseid);
    }

    /**
     * Builds the backlink cache for a course or for the whole instance (e.g. after purging the cache).
     *
     * @param int $courseid Id of the course, if 0 the cache will be built for the whole instance.
     * @return void
     */
    public static function build_backlink_cache(int $courseid = 0) {
        global $DB;
        $backlinks = [];
        $cache = \cache::make('mod_learningmap', 'backlinks');

        $conditions = ['backlink' => 1];
        if (!empty($courseid)) {
            $conditions['course'] = $courseid;
        }

        $records = $DB->get_records('learningmap', $conditions, '', 'id, placestore, backlink');
        foreach ($records as $record) {
            $module = get_coursemodule_from_instance('learningmap', $record->id, 0, true);
            $placestore = json_decode($record->placestore);
            $coursepageurl = course_get_format($module->course)->get_view_url($module->sectionnum);
            $coursepageurl->set_anchor('module-' . $module->id);
            foreach ($placestore->places as $place) {
                $url = !empty($module->showdescription) ?
                    $coursepageurl->out() :
                    new \moodle_url('/mod/learningmap/view.php', ['id' => $module->id]);
                $backlinks[$place->linkedActivity][$module->id] = [
                    'url' => $url,
                    'name' => $module->name,
                    'cmid' => $module->id,
                ];
            }
        }

        foreach ($backlinks as $cmid => $backlink) {
            $cache->set($cmid, $backlink);
        }

        if (empty($courseid)) {
            $cache->set('fillstate', time());
        }
    }

    /**
     * Removes a cmid from the backlink cache (e.g. when the course module was deleted).
     *
     * @param int $cmid Course module id
     * @return void
     */
    public static function remove_cmid(int $cmid) {
        $cache = \cache::make('mod_learningmap', 'backlinks');
        $cache->delete($cmid);
    }
}
