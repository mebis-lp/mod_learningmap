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

namespace mod_learningmap;

defined('MOODLE_INTERNAL') || die();

/**
 * Autoupdate class for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright   2021, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     https://www.gnu.org/licenses/agpl-3.0.html GNU AGPL v3 or later
 */
class autoupdate {
    /**
     * Called when a course_module_completion_updated event is triggered. Updates the completion state for all
     * learningmaps in the course of the activity.
     *
     * @param \core\event\base $event
     * @return void
     */
    public static function update_from_event(\core\event\base $event) : void {
        $data = $event->get_data();
        if (isset($data['courseid']) && $data['courseid'] > 0) {
            $modinfo = get_fast_modinfo($data['courseid']);
            $instances = $modinfo->get_instances_of('learningmap');
            if (count($instances) > 0) {
                $completion = new \completion_info($modinfo->get_course());
                foreach ($instances as $i) {
                    if (
                        $i->completion == COMPLETION_TRACKING_AUTOMATIC &&
                        $i->instance != $data['contextinstanceid']
                    ) {
                        $completion->update_state($i, COMPLETION_UNKNOWN, $data['userid']);
                    }
                }
            }
        }
    }
}
