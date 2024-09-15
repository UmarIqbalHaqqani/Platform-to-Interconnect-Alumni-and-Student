'use strict';

function cbxpoll_copyStringToClipboard (str) {
    // Create new element
    var el = document.createElement('textarea');
    // Set value (string to be copied)
    el.value = str;
    // Set non-editable to avoid focus and move outside of view
    el.setAttribute('readonly', '');
    el.style = {position: 'absolute', left: '-9999px'};
    document.body.appendChild(el);
    // Select text inside element
    el.select();
    // Copy text to clipboard
    document.execCommand('copy');
    // Remove temporary element
    document.body.removeChild(el);
}


jQuery(document).ready(function ($) {
    //click to copy shortcode
    $('.cbxpoll_ctp').on('click', function (e) {
        e.preventDefault();

        var $this = $(this);
        cbxpoll_copyStringToClipboard($this.prev('.cbxpollshortcode').text());

        $this.attr('aria-label', cbxpolladminlistingObj.copied);

        window.setTimeout(function () {
            $this.attr('aria-label', cbxpolladminlistingObj.copy);
        }, 1000);

    });
});