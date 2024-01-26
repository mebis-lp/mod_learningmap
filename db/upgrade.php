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

/**
 * Upgrade functions for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright 2021-2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     https://www.gnu.org/licenses/agpl-3.0.html GNU AGPL v3 or later
 */

/**
 * Define upgrade steps to be performed to upgrade the plugin from the old version to the current one.
 *
 * @param int $oldversion Version number the plugin is being upgraded from.
 */
function xmldb_learningmap_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2023080201) {
        $entries = $DB->get_records('learningmap', []);
        if ($entries) {
            foreach ($entries as $entry) {
                $placestore = json_decode($entry->placestore, true);
                $placestore['version'] = 2023080201;
                // Needs 1 as default value (otherwise all place strokes would be hidden).
                if (!isset($placestore['strokeopacity'])) {
                    $placestore['strokeopacity'] = 1;
                }
                $mapworker = new \mod_learningmap\mapworker($entry->intro, $placestore);
                $mapworker->replace_stylesheet();
                $mapworker->replace_defs();
                $entry->intro = $mapworker->get_svgcode();
                $entry->placestore = json_encode($placestore);
                $DB->update_record('learningmap', $entry);
            }
        }

        $table = new xmldb_table('learningmap');
        $field = new xmldb_field('backlink', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'completiontype');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $index = new xmldb_index('backlink', XMLDB_INDEX_NOTUNIQUE, ['backlink']);

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, 2023080201, 'learningmap');
    }

    return true;
}
