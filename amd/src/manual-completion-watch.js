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