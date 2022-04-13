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