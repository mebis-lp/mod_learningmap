# Learning maps
A learning map provides an easy way to improve the visualization of the activities in a moodle course.
Activities are represented as places on a map, connected by paths. Activities and paths are initially invisible. Every time an activity attached to a place is completed, the place changes its color (e.g. from red to green) and all connected paths and places are revealed.

## Use of learning maps
Learning maps can be used in many ways. They can show all activities of the course or only a part of it. They can also be nested to have different map levels (e.g. for a complete school year - one big map containing smaller maps for each topic).
Usually the activities show in the map are hidden but availabile for the participants. In this way the learning map can provide an easier way to build a path of dependent activities without using the moodle access restrictions.
Learning maps can be embedded on the course page (like a label) or shown on a separate page (like a page). You can use any activity with any type of activity completion.

## Start and completion
On a learning map you can define starting places which are visible by default. The first place you put on the map is a starting place by default.
You can also define places as target places. They can be used for automatic completion of the map in three different ways:
The map is completed if
* one target place is reached (this means the linked activity is completed)
* all target places are reached
* all places are reached

This is very useful if you used nested maps, you can also imitate parts of the behaviour of mod_checklist in this way.
The map is always updated when the completion state of a linked activity changes.

## Features
As a background image you can use any image which can be viewed by a web browser (e.g. JPG, PNG, GIF, SVG). The map is resized to the size of the image and is fully responsive.
You can change the color of places (different colors for visited / unvisited places) and the strokes for places and paths. Target places are highlighted for the participants. If necessary you can also hide the paths to the participants or show a checkmark in visited places.
If the learning map is embedded on a course page and manual completion of an linked activity is triggered, a page reload is forced to keep the map status correct.
If an activity has additional restrictions (e.g. being visible only after a certain date), learning map will display it only if these restrictions are fulfilled.

## Use of the editor
Using the editor is very easy:
* Choose your background image - it will be immediately shown in the map editor
* Add places (double click on the map)
* Link places to activities (right click on the place)
* Connect places by (single) clicking on both places
* Remove paths / places by double clicking on them

If a place is not linked to an activity it is shown with reduced opacity.


## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/mod/learningmap

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

The included DTD for SVG is licensed by the World Wide Web Consortium, see copyright
notice in pix/svg11.dtd

2021 ISB Bayern, Stefan Hanauska <stefan.hanauska@csg-in.de>
Icon by Dunja Speckner
Dragging of SVG elements by Peter Collingridge

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.