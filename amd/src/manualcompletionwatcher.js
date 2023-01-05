import {BaseComponent} from 'core/reactive';
import courseActions from 'core_course/actions';
import Log from 'core/log';
import events from "core_course/events";
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';



export default class extends BaseComponent {
  create(descriptor) {
    console.log('initalisiert initalisiert initalisiert')
    console.log(descriptor)
    this.element = descriptor.element;
    this.mapId = descriptor.mapId;
    this.dependingModuleIds = descriptor.dependingModuleIds;
  }

  stateReady(state) {
    console.log("state ready")
    console.log(state)
    //getCurrentCourseEditor().stateManager.target.addEventListener(events.stateChanged, event => console.log(event.detail.action));
  }

  getWatchers() {
    const watchers = [];
    this.dependingModuleIds.forEach(moduleId => {
      watchers.push({watch: `cm[${moduleId}].completionstate:updated`, handler: this.rerenderLearningmap});

    });
    console.log('watchers');
    console.log(watchers)
    return watchers;
  }

  // In this case we only want the affected element.
  rerenderLearningmap({element}) {
    console.log('rerendering...');
    courseActions.refreshModule(document.getElementById('module-' + this.mapId), this.mapId);
  }
}
