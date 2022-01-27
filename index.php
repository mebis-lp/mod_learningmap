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

/**
 * Index.php for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright   2021, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     https://www.gnu.org/licenses/agpl-3.0.html GNU AGPL v3 or later
 */

require_once("../../config.php");
require_once("lib.php");

$id = required_param('id', PARAM_INT);
require_login($id, true);

$PAGE->set_url('/mod/learningmap/index.php', ['id' => $id]);

redirect($CFG->wwwroot . '/course/view.php?id=' . $id);
