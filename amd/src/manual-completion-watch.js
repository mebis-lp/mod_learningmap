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
 * This module only servers backwards compatibility for moodle versions 3.9 and 3.11.
 * As soon as the support is being dropped, this can be removed as well as the call in lib.php::learningmap_cm_info_view
 *
 * @param {array} coursemodules course modules the learningmap depends on
 */
export const init = (coursemodules) => {
    coursemodules.forEach((c) => {
        let selector = '[data-action="toggle-manual-completion"][data-cmid="' + c + '"]';
        let el = document.querySelector(selector);
        if (el) {
            el.addEventListener('click', function() {
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            });
        }
        // For moodle 3.9 / 3.10 compatibility
        selector = '.togglecompletion';
        let els = Array.from(document.querySelectorAll(selector));
        els.forEach(function(el) {
            let idfield = el.querySelector('input[name="id"]');
            if (idfield && idfield.getAttribute('value') == c) {
                el.addEventListener('submit', function() {
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                });
            }
        });
    });
};
