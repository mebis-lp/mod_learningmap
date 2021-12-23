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
    });
};