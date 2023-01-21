import Ajax from 'core/ajax';
import Log from 'core/log';

const selectors = {
  LEARNINGMAP_RENDER_CONTAINER_PREFIX: 'learningmap-render-container-'
};

export const init = (cmId) => {
  const promises = Ajax.call(
      [
        {
          methodname: 'mod_learningmap_get_learningmap',
          args: {'cmId': cmId},
        }
      ]);

  promises[0].then(data => {
    const targetDiv = document.getElementById(
        selectors.LEARNINGMAP_RENDER_CONTAINER_PREFIX + cmId);
    targetDiv.innerHTML = data.content;
    return true;
  }).catch((error) => {
    Log.error(error);
    return false;
  });
};
