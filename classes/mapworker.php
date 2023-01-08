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
 * Class for handling the content of the learningmap
 *
 * @package     mod_learningmap
 * @copyright   2021-2022, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mapworker {
    /**
     * @var $dom DOMDocument for parsing the SVG
     */
    protected $dom;
    /**
     * @var $svgcode String containing the SVG code (synchronized with $dom)
     */
    protected $svgcode;
    /**
     * @var $placestore Array containing the placestore
     */
    protected $placestore;
    /**
     * @var $prepend String to prepend to the SVG code (for parsing by DOMDocument)
     */
    protected $prepend;
    /**
     * @var $cm Course module object belonging to the map - only needed for completion
     */
    protected $cm;
    /**
     * @var $edit Whether to prepare the code for edit mode
     */
    protected $edit;
    /**
     * @var array $this->coordinates Stores the coordinates of visible places and paths
     */
    protected $coordinates;

    /**
     * Creates mapworker from SVG code
     *
     * @param string $svgcode The SVG code to build the map from
     * @param array $placestore The placestore data to use while processing the map
     * @param cm_info $cm The course module that belongs to the map (null by default)
     * @param bool $edit Whether the mapworker should prepare the map for edit mode (false by default)
     */
    public function __construct(string $svgcode, array $placestore, \cm_info $cm = null, bool $edit = false) {
        global $CFG;
        $this->svgcode = $svgcode;
        $this->placestore = $placestore;
        $this->edit = $edit;
        $placestore['editmode'] = $this->edit;
        $this->coordinates = [];
        if (!is_null($cm)) {
            $this->cm = $cm;
        }
        // This fixes a problem for loading SVG DTD on Windows locally.
        if (strcasecmp(substr(PHP_OS, 0, 3), 'WIN') == 0) {
            $dtd = '' . new \moodle_url('/mod/learningmap/pix/svg11.dtd');
        } else {
            $dtd = $CFG->dirroot . '/mod/learningmap/pix/svg11.dtd';
        }
        $this->prepend = '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "' . $dtd . '">';

        $this->dom = new \DOMDocument('1.0', 'UTF-8');
        $this->dom->validateOnParse = true;
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = true;

        $this->load_dom();
    }

    /**
     * Loads the code from svgcode attribute for DOM processing
     *
     * @return void
     */
    public function load_dom() : void {
        $this->remove_tags_before_svg();
        $this->dom->loadXML($this->prepend . $this->svgcode);
    }

    /**
     * Replaces the stylesheet with a new one generated from placestore
     *
     * @param array $placestoreoverride array of overrides for placestore
     * @return void
     */
    public function replace_stylesheet(array $placestoreoverride = []) : void {
        global $OUTPUT;
        $placestorelocal = array_merge($this->placestore, $placestoreoverride);
        $placestorelocal = array_merge($placestorelocal, ['editmode' => $this->edit]);
        $this->svgcode = preg_replace(
            '/<style[\s\S]*style>/i',
            $OUTPUT->render_from_template('mod_learningmap/cssskeleton', $placestorelocal),
            $this->svgcode
        );
        $this->load_dom();
    }

    /**
     * Removes tags before the SVG tag to avoid parsing problems
     *
     * @return void
     */
    public function remove_tags_before_svg() : void {
        $remove = ['<?xml version="1.0"?>', $this->prepend];
        $this->svgcode = str_replace($remove, '', $this->svgcode);
    }

    /**
     * Process the map to show / hide paths and places
     * @return void
     */
    public function process_map_objects() : void {
        global $CFG, $USER;
        $active = [];
        $completedplaces = [];
        $notavailable = [];
        $impossible = [];
        $allplaces = [];
        $links = [];

        $modinfo = get_fast_modinfo($this->cm->get_course(), $USER->id);

        $allcms = array_keys($modinfo->get_cms());

        // Walk through all places in the map.
        foreach ($this->placestore['places'] as $place) {
            $allplaces[] = $place['id'];
            // Get the id of the link in the DOM.
            $link = $this->dom->getElementById($place['linkId']);
            // Only if the place is linked to an activity.
            if (!empty($place['linkedActivity'])) {
                // Only get modinfo for the activity if it is in the array of course module ids.
                if (in_array($place['linkedActivity'], $allcms)) {
                    $placecm = $modinfo->get_cm($place['linkedActivity']);
                } else {
                    $placecm = false;
                }
                $placenode = $this->dom->getElementById($place['id']);
                if ($placenode) {
                    $cx = intval($placenode->getAttribute('cx'));
                    $cy = intval($placenode->getAttribute('cy'));
                    $this->coordinates[$place['id']]['x'] = $cx;
                    $this->coordinates[$place['id']]['y'] = $cy;
                    if ($this->placestore['showtext']) {
                        $text = $this->dom->getElementById('text' . $place['id']);
                        if ($text) {
                            $dx = $text->getAttribute('dx');
                            $dy = $text->getAttribute('dy');
                            $bbox = imagettfbbox(20, 0, $CFG->dirroot . '/lib/default.ttf', $text->nodeValue);
                            $this->coordinates['text1' . $place['id']]['x'] = $cx + $dx + $bbox[0];
                            $this->coordinates['text1' . $place['id']]['y'] = $cy + $dy + $bbox[1];
                            $this->coordinates['text2' . $place['id']]['x'] = $cx + $dx + $bbox[2];
                            $this->coordinates['text2' . $place['id']]['y'] = $cy + $dy + $bbox[3];
                            $this->coordinates['text3' . $place['id']]['x'] = $cx + $dx + $bbox[4];
                            $this->coordinates['text3' . $place['id']]['y'] = $cy + $dy + $bbox[5];
                            $this->coordinates['text4' . $place['id']]['x'] = $cx + $dx + $bbox[6];
                            $this->coordinates['text4' . $place['id']]['y'] = $cy + $dy + $bbox[7];
                        }
                    }
                }
                // If the activity is not found or if there is no activity, add it to the list of not available places.
                // Remove the place completely from the map.
                if (!$placecm) {
                    $impossible[] = $place['id'];
                    if (!$this->edit) {
                        $link->parentNode->removeChild($link);
                    }
                } else {
                    // Set the link URL in the map.
                    if (!empty($link)) {
                        if (!empty($placecm->url)) {
                            // Link modules that have a view page to their corresponding url.
                            $url = '' . $placecm->url;
                        } else {
                            // Other modules (like labels) are shown on the course page. Link to the corresponding anchor.
                            $url = $CFG->wwwroot . '/course/view.php?id=' . $placecm->course .
                                '&section=' . $placecm->sectionnum . '#module-' . $placecm->id;
                        }
                        if (!$this->edit) {
                            $link->setAttribute(
                                'xlink:href',
                                $url
                            );
                        }
                        $links[$place['id']] = $place['linkId'];
                        // Set the title element for the link (for accessibility) and for a tooltip when hovering
                        // the link.
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
                        // Set the text element for the link.
                        $text = $this->dom->getElementById('text' . $place['id']);
                        if ($text) {
                            $text->nodeValue = $placecm->get_formatted_name();
                        }
                    }
                    // If the place is a starting place, add it to the active places.
                    if (in_array($place['id'], $this->placestore['startingplaces'])) {
                        $active[] = $place['id'];
                    }
                    // If the activity linked to the place is already completed, add it to the completed
                    // and to the active places.
                    if ($this->is_completed($placecm)) {
                        $completedplaces[] = $place['id'];
                        $active[] = $place['id'];
                    }
                    // Places that are not accessible (e.g. because of additional availability restrictions)
                    // are only shown on the map if showall mode is active.
                    if (!$placecm->available) {
                        $notavailable[] = $place['id'];
                    }
                    // Places that are not visible and not in stealth mode (i.e. reachable by link)
                    // are impossible to reach.
                    if ($placecm->visible == 0 && !$placecm->is_stealth()) {
                        $impossible[] = $place['id'];
                    }
                }
                // If the place is not linked to an activity it is impossible to reach.
            } else {
                $impossible[] = $place['id'];
                if (!$this->edit) {
                    $link->parentNode->removeChild($link);
                }
            }
        }
        if (!($this->edit)) {
            foreach ($this->placestore['paths'] as $path) {
                $pathnode = $this->dom->getElementById($path['id']);
                if ($pathnode && strpos($pathnode->getAttribute('d'), 'Q')) {
                    $parts = explode(' ', $pathnode->getAttribute('d'));
                    $fromx = intval($parts[1]);
                    $fromy = intval($parts[2]);
                    $betweenx = intval($parts[4]);
                    $betweeny = intval($parts[5]);
                    $tox = intval($parts[6]);
                    $toy = intval($parts[7]);
                    $coordx = $betweenx * 0.5 + ($fromx + $tox) * 0.25;
                    $coordy = $betweeny * 0.5 - ($fromy + $toy) * 0.25;
                    $this->coordinates[$path['id']]['x'] = intval($coordx);
                    $this->coordinates[$path['id']]['y'] = intval($coordy);
                }
                // If the ending of the path is a completed place and this place is available,
                // show path and the place on the other end.
                if (in_array($path['sid'], $completedplaces) && !in_array($path['fid'], array_merge($notavailable, $impossible))) {
                    // Only set paths visible if hidepaths is not set in placestore.
                    if (!$this->placestore['hidepaths']) {
                        $active[] = $path['id'];
                    }
                    $active[] = $path['fid'];
                }
                // If the beginning of the path is a completed place and this place is available,
                // show path and the place on the other end.
                if (in_array($path['fid'], $completedplaces) && !in_array($path['sid'], array_merge($notavailable, $impossible))) {
                    // Only set paths visible if hidepaths is not set in placestore.
                    if (!$this->placestore['hidepaths']) {
                        $active[] = $path['id'];
                    }
                    $active[] = $path['sid'];
                }
                // Hide paths that lead to unreachable places.
                if (!empty($this->placestore['showall'])) {
                    if (in_array($path['sid'], $impossible) || in_array($path['fid'], $impossible)) {
                        $dompath = $this->dom->getElementById($path['id']);
                        if ($dompath) {
                            $dompath->setAttribute('style', 'visibility: hidden;');
                        }
                    }
                }
            }
            // Set all active paths and places to visible.
            foreach ($active as $a) {
                $domplace = $this->dom->getElementById($a);
                if (!$domplace) {
                    continue;
                }
                $domplace->setAttribute('class', $domplace->getAttribute('class') . ' learningmap-reachable');
            }
            // Make all completed places visible and set color for visited places.
            foreach ($completedplaces as $place) {
                $domplace = $this->dom->getElementById($place);
                if ($domplace) {
                    if (!isset($this->placestore['version'])) {
                        $domplace->setAttribute('style', 'visibility: visible; fill: ' . $this->placestore['visitedcolor'] . ';');
                    } else {
                        $domplace->setAttribute('class', $domplace->getAttribute('class') . ' learningmap-place-visited');
                    }
                    // If the option "usecheckmark" is selected, add the checkmark to the circle.
                    if ($this->placestore['usecheckmark']) {
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
            $notavailable = array_merge(
                array_diff($allplaces, $notavailable, $completedplaces, $active, $impossible),
                $notavailable
            );
            // Handle unavailable places.
            foreach ($notavailable as $place) {
                $domplace = $this->dom->getElementById($place);
                unset($this->coordinates[$place]);
                for ($i = 1; $i < 5; $i++) {
                    unset($this->coordinates['text' . $i . $place]);
                }
                if (!$domplace) {
                    continue;
                }
                if (isset($links[$place])) {
                    $domlink = $this->dom->getElementById($links[$place]);
                    $domlink->setAttribute('class', $domplace->getAttribute('class') . ' learningmap-hidden');
                    $domlink->removeAttribute('xlink:href');
                }
            }
            // Make all places hidden if they are impossible to reach.
            foreach ($impossible as $place) {
                $domplace = $this->dom->getElementById($place);
                if (!$domplace) {
                    continue;
                }
                if (isset($links[$place])) {
                    $domlink = $this->dom->getElementById($links[$place]);
                    $domlink->setAttribute('style', 'visibility: hidden;');
                    $domlink->removeAttribute('xlink:href');
                }
            }
        }

        if (
            !$this->edit &&
            !empty($this->placestore['slicemode']) &&
            count($this->coordinates) > 0 &&
            count($notavailable) + count($impossible) > 0
        ) {
            $c = array_pop($this->coordinates);
            $minx = $c['x'];
            $miny = $c['y'];
            $maxx = $c['x'];
            $maxy = $c['y'];

            foreach ($this->coordinates as $item => $coord) {
                $minx = min($minx, $coord['x']);
                $miny = min($miny, $coord['y']);
                $maxx = max($maxx, $coord['x']);
                $maxy = max($maxy, $coord['y']);
            }

            if (count($this->coordinates) == 0 || ($maxx - $minx < 100 && $maxy - $miny < 100)) {
                $padding = 50;
            } else {
                $padding = 15;
            }
            $minx = max(0, $minx - $padding);
            $miny = max(0, $miny - $padding);
            $maxx = min(800, $maxx + $padding);
            $maxy = min(600, $maxy + $padding);

            $this->dom->getElementById('learningmap-svgmap-' . $this->placestore['mapid'])->setAttribute(
                'viewBox',
                $minx . ' ' . $miny . ' ' . ($maxx - $minx) . ' ' . ($maxy - $miny)
            );
            $this->dom->getElementById('learningmap-svgmap-' . $this->placestore['mapid'])->setAttribute('width', $maxx - $minx);
            $this->dom->getElementById('learningmap-svgmap-' . $this->placestore['mapid'])->setAttribute('height', $maxy - $miny);
            $this->dom->getElementById('learningmap-svgmap-' . $this->placestore['mapid'])->setAttribute(
                'class',
                'learningmap-scaling-svg learningmap-slicemode'
            );
        }

        $this->svgcode = $this->dom->saveXML();
    }

    /**
     * Returns the current svg code
     *
     * @return string
     */
    public function get_svgcode(): string {
        return $this->svgcode;
    }

    /**
     * Checks whether a given course module is completed (either by the user or at least one
     * of the users of the group, if groupmode is set for the activity).
     *
     * @param \cm_info $cm course module to check
     */
    public function is_completed(\cm_info $cm): bool {
        global $USER;
        if (!isset($this->cm)) {
            return false;
        }
        $completion = new \completion_info($cm->get_course());
        if (!empty($this->cm->groupmode)) {
            $group = groups_get_activity_group($this->cm, true);
        }
        if (!empty($group)) {
            $members = groups_get_members($group);
        }
        if (empty($members)) {
            $members = [$USER];
        }
        foreach ($members as $member) {
            if ($completion->get_data($cm, true, $member->id)->completionstate == COMPLETION_COMPLETE ||
                $completion->get_data($cm, true, $member->id)->completionstate == COMPLETION_COMPLETE_PASS) {
                return true;
            }
        }
        return false;
    }
}
