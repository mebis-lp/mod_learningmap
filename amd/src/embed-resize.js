export const init = (iframeid) => {
    /**
     * Resize the learningmap iframe to show all of the content.
     */
    function resizeIframe() {
        let height = document.getElementById(iframeid).contentWindow.document.body.querySelector('[role="main"]').scrollHeight;
        let width = document.getElementById(iframeid).contentWindow.document.body.querySelector('[role="main"]').scrollWidth;
        document.getElementById(iframeid).style.height = height + 'px';
        document.getElementById(iframeid).style.aspectRatio = parseInt(width) + '/' + parseInt(height);
    }
    let iframe = document.getElementById(iframeid);
    iframe.addEventListener('load', resizeIframe);
    var ro = new ResizeObserver(() => {
        resizeIframe();
    });

    ro.observe(iframe.parentElement);
};
