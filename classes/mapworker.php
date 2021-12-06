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

use DOMDocument;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for handling the content of the learningmap
 *
 * @package     mod_learningmap
 * @copyright   2021, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mapworker {
    protected DOMDocument $dom;
    protected string $svgcode;
    protected array $placestore;
    protected string $prepend;

    /**
     * Creates mapworker from SVG code
     *
     * @param string $svgcode
     * @param array $this->placestore
     */
    function __construct(string $svgcode, array $placestore) {
        global $CFG;
        $this->svgcode = $svgcode;
        $this->placestore = $placestore;

        $this->prepend = '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "' . $CFG->dirroot . '/mod/learningmap/pix/svg11.dtd">';

        $this->dom = new \DOMDocument('1.0', 'UTF-8');
        $this->dom->validateOnParse = true;
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = true;

        $this->loadDOM();
    }

    /**
     * Loads the code from svgcode attribute for DOM processing
     *
     * @return void
     */
    function loadDOM() : void {
        $this->remove_tags_before_svg();
        $this->dom->loadXML($this->prepend . $this->svgcode);
    }

    /**
     * Replaces the stylesheet with a new one generated from placestore
     *
     * @param array $this->placestore_override array of overrides for placestore
     * @return void
     */
    function replace_stylesheet(array $placestore_override = []) : void {
        global $OUTPUT;
        $this->placestore_local = array_merge($this->placestore, $placestore_override);
        $this->svgcode = preg_replace(
            '/<style[\s\S]*style>/i',
            $OUTPUT->render_from_template('mod_learningmap/cssskeleton', $this->placestore_local),
            $this->svgcode
        );
        $this->loadDOM();
    }

    /**
     * Removes tags before the SVG tag to avoid parsing problems
     *
     * @return void
     */
    function remove_tags_before_svg() : void {
        $remove = ['<?xml version="1.0"?>', $this->prepend];
        $this->svgcode = str_replace($remove, '', $this->svgcode);
    }

    /**
     * Process the map to show / hide paths and places
     * @param cm_info $cm
     * @return void
     */
    function process_map_objects(\cm_info $cm) : void {
        global $USER;
        $active = [];
        $completedplaces = [];
        $notavailable = [];

        $completion = new \completion_info($cm->get_course());

        foreach ($this->placestore['places'] as $place) {
            $link = $this->dom->getElementById($place['linkId']);
            if ($place['linkedActivity'] != null) {
                try {
                    $placecm = get_fast_modinfo($cm->get_course(), $USER->id)->get_cm($place['linkedActivity']);
                } catch (\Exception $e) {
                    $placecm = false;
                }
                if (!$placecm) {
                    $notavailable[] = $place['id'];
                    $link->parentNode->removeChild($link);
                } else {
                    if ($link) {
                        $link->setAttribute(
                            'xlink:href',
                            new \moodle_url('/mod/' . $placecm->modname . '/view.php', ['id' => $placecm->id])
                        );
                        $title = $this->dom->getElementById('title' . $place['id']);
                        if ($title) {
                            $title->nodeValue =
                                $placecm->get_formatted_name() .
                                (
                                    // Add info to target places (for accessibility).
                                    in_array($place['id'], $this->placestore['targetplaces']) ?
                                    ' (' . get_string('targetplace', 'learningmap') . ')' :
                                    ''
                                );
                        }
                    }
                    if (in_array($place['id'], $this->placestore['startingplaces'])) {
                        $active[] = $place['id'];
                    }
                    if ($completion->get_data($placecm, true, $USER->id)->completionstate > 0) {
                        $completedplaces[] = $place['id'];
                        $active[] = $place['id'];
                    }
                }
            } else {
                $notavailable[] = $place['id'];
                $link->parentNode->removeChild($link);
            }
        }
        // Only set paths visible if hidepaths is not set in placestore
        if (!$this->placestore['hidepaths']) {
            foreach ($this->placestore['paths'] as $path) {
                // If the ending of the path is a completed place and this place is availabile,
                // show path and the place on the other end.
                if (in_array($path['sid'], $completedplaces) && !in_array($path['fid'], $notavailable)) {
                    $active[] = $path['id'];
                    $active[] = $path['fid'];
                }
                // If the beginning of the path is a completed place and this place is availabile,
                // show path and the place on the other end.
                if (in_array($path['fid'], $completedplaces) && !in_array($path['sid'], $notavailable)) {
                    $active[] = $path['id'];
                    $active[] = $path['sid'];
                }
            }
        }
        // Set all active paths and places to visible
        foreach ($active as $a) {
            $domplace = $this->dom->getElementById($a);
            if ($domplace) {
                $domplace->setAttribute('style', 'visibility: visible;');
            }
        }
        // Make all completed places visible and set color for visited places
        foreach ($completedplaces as $place) {
            $domplace = $this->dom->getElementById($place);
            if ($domplace) {
                $domplace->setAttribute('style', 'visibility: visible; fill: ' . $this->placestore['visitedcolor'] . ';');
                if($this->placestore['usecheckmark']) {
                    $x = $domplace->getAttribute('cx');
                    $y = $domplace->getAttribute('cy');
                    $use = $this->dom->createElement('use');
                    $use->setAttribute('xlink:href', '#checkmark');
                    $use->setAttribute('transform', 'translate(' . $x . ' '. $y . ')');
                    $use->setAttribute('class', 'learningmap-checkmark');
                    $domplace->parentNode->appendChild($use);
                }
            }
        }
        // Make all places hidden if they are not availabile
        foreach ($notavailable as $place) {
            $domplace = $this->dom->getElementById($place);
            if ($domplace) {
                $domplace->setAttribute('style', 'visibility: hidden;');
            }
        }
        $this->svgcode = $this->dom->saveXML();
    }

    /**
     * Returns the current svg code
     *
     * @return string
     */
    function get_svgcode(): string {
        return $this->svgcode;
    }
}