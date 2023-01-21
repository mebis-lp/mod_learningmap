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
 * mod_learningmap externallib for fetching the learningmap via ajax.
 *
 * @package    mod_learningmap
 * @copyright  2023 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

/**
 * External lib class for mod_learningmap.
 *
 * @copyright  2023 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_learningmap_external extends external_api {

    /**
     * Returns description of method parameters for the get_learningmap webservice function.
     *
     * @return external_function_parameters
     */
    public static function get_learningmap_parameters(): external_function_parameters {
        return new external_function_parameters(['cmId' => new external_value(PARAM_INT,
            'Course module ID of the learningmap')]);
    }

    /**
     * Definition of return values of the get_learningmap webservice function.
     *
     * @return external_single_structure
     */
    public static function get_learningmap_returns(): external_single_structure {
        return
            new external_single_structure(
                [
                    'content' => new external_value(PARAM_RAW, 'Rendered learningmap')
                ]
            );
    }

    /**
     * The actual method returning the rendered learningmap HTML code via webservice.
     *
     * @param int $cmid the course module id of the learningmap which HTML code should be retrieved
     * @return array The raw HTML in an array of the form ['content' => 'learningmap HTML code' ]
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws moodle_exception
     */
    public static function get_learningmap(int $cmid): array {
        $params = self::validate_parameters(self::get_learningmap_parameters(), ['cmId' => $cmid]);
        $cmid = $params['cmId'];
        list($course, $cminfo) = get_course_and_cm_from_cmid($cmid);
        require_course_login($course);
        $context = context_module::instance($cmid);
        require_capability('mod/learningmap:view', $context);
        return ['content' => learningmap_get_learningmap($cminfo)];
    }
}
