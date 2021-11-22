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
 * mod_learningmap data generator
 *
 * @package mod_learningmap
 * @copyright  2021 Stefan Hanauska <stefan.hanauska@altmuehlnet.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class mod_learningmap_generator extends testing_module_generator {

    public function create_instance($record = null, array $options = null) {
        global $CFG;

        $record = (array)$record + array(
            'name' => 'test map',
            'intro' => '<svg id="learningmap-svgmap" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://www.w3.org/2000/svg" version="1.1" baseProfile="full" width="800" height="600">
            <style id="svgstyle" type="text/css">
            .place {
                fill: red;
                stroke-width: 3px;
                stroke: white;
                visibility: hidden;
            }
            .path {
                stroke: white;
                stroke-width: 3px;
                visibility: hidden;
            }
            </style><g id="backgroundGroup">
                <image x="0" y="0" width="800" height="600" class="learningmap-background-image" id="learningmap-background-image" xlink:href="@@PLUGINFILE@@/Beispiel%20Hintergrund.jpg"></image>
            </g>
            <g id="pathsGroup">
            <line class="path" id="p0_1" x1="287" y1="510" x2="230" y2="385"></line><line class="path" id="p1_3" x1="230" y1="385" x2="340" y2="294"></line><line class="path" id="p1_2" x1="230" y1="385" x2="88" y2="341"></line></g>
            <g id="placesGroup">
            <a id="a0" xlink:href=""><circle class="place draggable" id="p0" cx="287" cy="510" r="10"></circle><title id="titlep0"></title></a><a id="a1" xlink:href=""><circle class="place draggable" id="p1" cx="230" cy="385" r="10"></circle><title id="titlep1"></title></a><a id="a2" xlink:href=""><circle class="place draggable selected2" id="p2" cx="88" cy="341" r="10"></circle><title id="titlep2"></title></a><a id="a3" xlink:href=""><circle class="place draggable" id="p3" cx="340" cy="294" r="10"></circle><title id="titlep3"></title></a></g>
            </svg>',
            'introformat' => 1,
            'placestore' => '{"id":4,"places":[{"id":"p0","linkId":"a0","linkedActivity":"1038"},{"id":"p1","linkId":"a1","linkedActivity":"1046"},{"id":"p2","linkId":"a2","linkedActivity":"1048"},{"id":"p3","linkId":"a3","linkedActivity":"1048"}],"paths":[{"id":"p0_1","fid":"p0","sid":"p1"},{"id":"p1_3","fid":"p1","sid":"p3"},{"id":"p1_2","fid":"p1","sid":"p2"}],"startingplaces":["p0"],"placecolor":"red","strokecolor":"white"}'
        );

        return parent::create_instance($record, (array)$options);
    }
}
