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

namespace mod_learningmap\output;

/**
 * Class mobile
 *
 * @package    mod_learningmap
 * @copyright  2025 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {
    /**
     * Render the mobile view for the learning map
     *
     * @param array $args
     * @return array
     */
    public static function mobile_learningmap_view(array $args): array {
        global $OUTPUT;

        $cm = get_coursemodule_from_id('learningmap', $args['cmid']);
        
        require_login($cm->course, false, $cm);
        $context = context_module::instance($cm->id);
        require_capability('mod/learningmap:view', $context);

        $result = [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template(
                        'mod_learningmap/rendercontainer',
                        ['cmId' => $cm->id, 'enableLiveUpdater' => false, 'contentbeforemap' => '', 'hascontentbeforemap' => false]
                    ),
                ],
            ],
            'javascript' => '',
            'otherdata' => '',
        ];

        return $result;
    }
}
