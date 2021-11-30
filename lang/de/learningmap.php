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
 * Language file for mod_learningmap
 *
 * @package     mod_learningmap
 * @copyright   2021, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Lernlandkarte';
$string['modulenameplural'] = 'Lernlandkarten';
$string['modulename_help'] = 'Lernlandkarten erlauben es, die Aktivitäten in einem Kurs als durch Pfade verbundene Orte auf einer Landkarte darzustellen. Einzelne Orte sind Start-Orte und werden zu Beginn dargestellt. Andere Orte und Pfade werden angezeigt, sobald die Aktivitäten der verbundenen Orte abgeschlossen sind.';
$string['pluginname'] = 'Lernlandkarte';
$string['name'] = 'Titel der Lernlandkarte';
$string['name_help'] = 'Der Titel der Lernlandkarte wird nur angezeigt, wenn "Karte auf Kursseite anzeigen" nicht aktiviert ist.';
$string['pluginadministration'] = 'Lernlandkarte Administration';
$string['backgroundfile'] = 'Hintergrundbild';
$string['backgroundfile_help'] = 'Diese Datei wird als Hintergrund für die Lernlandkarte verwendet.';
$string['svgcode'] = 'SVG Code';
$string['learningmap'] = 'Lernlandkarte';
$string['editplace'] = 'Ort bearbeiten';
$string['startingplace'] = 'Start-Ort';
$string['targetplace'] = 'Ziel-Ort';
$string['showdescription'] = 'Karte auf Kursseite anzeigen';
$string['showdescription_help'] = 'Wenn diese Option aktiviert ist, wird die Lernlandkarte auf der Kursseite angezeigt. Ist sie deaktiviert, wird die Lernlandkarte auf einer separaten Seite dargestellt.';
$string['intro'] = $string['learningmap'];
$string['intro_help'] = 'Hinzufügen eines Ortes: Doppelklick
Hinzufügen eines Pfades: Zwei Orte nacheinander anklicken
Entfernen eines Ortes: Doppelklick auf den Ort
Entfernen eines Pfades: Doppelklick auf den Pfad
Eigenschaften des Ortes verändern: Rechtsklick';
$string['learningmap:addinstance'] = 'Lernlandkarte hinzufügen';
$string['learningmap:view'] = 'Lernlandkarte anzeigen';
$string['completiontype'] = 'Art des Aktivitätsabschlusses';
$string['nocompletion'] = 'Keine automatischer Abschluss über die Karte';
$string['completion_with_one_target'] = 'Ein Ziel-Ort muss erreicht werden';
$string['completion_with_all_targets'] = 'Alle Ziel-Orte müssen erreicht werden';
$string['completion_with_all_places'] = 'Alle Orte müssen erreicht werden';
$string['completiondetail:one_target'] = 'Einen Ziel-Ort erreichen';
$string['completiondetail:all_targets'] = 'Alle Ziel-Orte erreichen';
$string['completiondetail:all_places'] = 'Alle Orte erreichen';
$string['privacy:metadata'] = '';
$string['places'] = 'Orte';
$string['visited'] = 'Besucht';
$string['paths'] = 'Pfade';
$string['hidepaths'] = 'Pfade verbergen';
