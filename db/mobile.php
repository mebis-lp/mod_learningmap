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
 * Mobile app areas for Learning map
 *
 * Documentation: {@link https://moodledev.io/general/app/development/plugins-development-guide}
 *
 * @package    mod_learningmap
 * @copyright  2025 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'mod_learningmap' => [
        'handlers' => [
            'learningmap_view_separate' => [
                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'mobile_learningmap_view',
                'displaydata' => [
                    'icon' => $CFG->wwwroot . '/mod/learningmap/pix/icon.svg',
                    'class' => '',
                ],
                'styles' => [
                    'url' => $CFG->wwwroot . '/mod/learningmap/styles.css',
                ],
            ],
            'learningmap_view_embedded' => [
                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'mobile_learningmap_view',
                'displaydata' => [
                    'icon' => $CFG->wwwroot . '/mod/learningmap/pix/icon.svg',
                    'class' => '',
                ],
                'styles' => [
                    'url' => $CFG->wwwroot . '/mod/learningmap/styles.css',
                ],
            ],
        ],
        'lang' => [
            ['pluginname', 'mod_learningmap'],
        ],
    ],
  ];
