export const init = (iframeid) => {
    /**
     * Resize the learningmap iframe to show all of the content.
     */
    function resizeIframe() {
        document.getElementById(iframeid).style.height =
        document.getElementById(iframeid).contentWindow.document.body.querySelector('[role="main"]').scrollHeight + 'px';
    }
    let iframe = document.getElementById(iframeid);
    iframe.addEventListener('load', resizeIframe);
    var ro = new ResizeObserver(() => {
        resizeIframe();
    });

    ro.observe(iframe.parentElement);
};
