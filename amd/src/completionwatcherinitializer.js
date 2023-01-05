import CompletionWatcher from 'mod_learningmap/manualcompletionwatcher';
import {getCurrentCourseEditor} from 'core_courseformat/courseeditor';


export const init = (moduleId, dependingModuleIds) => {
  console.log("einstieg")
  return new CompletionWatcher({
    element: document.getElementById('module-' + moduleId),
    reactive: getCurrentCourseEditor(),
    mapId: moduleId,
    dependingModuleIds: dependingModuleIds
  });
};
