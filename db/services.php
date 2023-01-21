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
 * mod_learningmap service definition.
 *
 * @package    mod_learningmap
 * @copyright  2023 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$services = [
    'mod_learningmap' => [
        'functions' => ['mod_learningmap_get_learningmap'],
        'requiredcapability' => '',
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'mod_learningmap',
        'downloadfiles' => 0,
        'uploadfiles'  => 0
    ]
];

$functions = [
    'mod_learningmap_get_learningmap' => [
        'classname'   => 'mod_learningmap_external',
        'classpath'   => 'mod/learningmap/externallib.php',
        'methodname'  => 'get_learningmap',
        'description' => 'Retrieves the learningmap',
        'type'        => 'read',
        'ajax' => true,
        'services' => ['mod_learningmap'],
        'capabilities' => '',
    ]
];
