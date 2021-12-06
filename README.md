# Learningmap #

This plugin allows to visualize activities in a course as places on a map.
The places are connected through paths. When an activity is completed, all
connected places and paths are shown on the map.

The user can define the layout of the map:
- The background image (jpg, png, svg)
- The location of the places
- The paths connecting the places
- The color of places, visited places (this means the activity is completed) and paths
- Whether the paths are shown on the map
- Which places are starting places (this means they are shown on the map from the beginning)
- Which places are target places (important for automatic completion)

The map can be shown on the course page (like a label) or on a separate page.

The activity supports the following completion mechanisms:
- Completion by view
- Completion by reaching one target place
- Completion by reaching all target places
- Completion by visiting all places

When the activity is embedded on course page and there are activities with manual completion
visible on the same course page, the plugin triggers a page refresh when ticking / unticking
manual completion.


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

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.

