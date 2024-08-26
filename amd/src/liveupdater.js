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
 * Live updater component for mod_learningmap.
 *
 * @module     mod_learningmap/liveupdater
 * @copyright 2021-2024, ISB Bayern
 * @author     Philipp Memmel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {BaseComponent} from 'core/reactive';
import {refreshModule} from 'core_course/actions';
import {renderLearningmap} from 'mod_learningmap/renderer';

/**
 * The live updater component.
 */
export default class extends BaseComponent {
    create(descriptor) {
        this.element = descriptor.element;
        this.reactive = descriptor.reactive;
        this.cmId = descriptor.cmId;
        this.dependingModuleIds = descriptor.dependingModuleIds;
    }

    getWatchers() {
        const watchers = [];
        this.dependingModuleIds.forEach(moduleId => {
            watchers.push({watch: `cm[${moduleId}].completionstate:updated`, handler: this._rerenderLearningmap});
            watchers.push({watch: `cm[${moduleId}].name:updated`, handler: this._rerenderLearningmap});
        });
        return watchers;
    }

    /**
     * Handler for triggering the rerendering of the learningmap.
     */
    _rerenderLearningmap() {
        // We need this to update the automatic completion status. Unfortunately, this old function does not update the
        // JS, so we also need to render the learningmap afterwards.
        refreshModule(this.element, this.cmId);
        renderLearningmap(this.cmId);
    }
}
