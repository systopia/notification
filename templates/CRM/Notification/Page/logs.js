/* templates/CRM/Notification/Page/logs.js */
(function (cj) {
  'use strict';

  function toPretty(val) {
    try {
      if (typeof val === 'string') {
        var s = val.trim();
        if (s && (s[0] === '{' || s[0] === '[')) {
          return JSON.stringify(JSON.parse(s), null, 2);
        }
        return val;
      }
      return JSON.stringify(val, null, 2);
    } catch (e) {
      return String(val);
    }
  }

  function openContextDialog(fullVal, id) {
    var pretty = toPretty(fullVal);
    var $content = cj('<div class="notiflog-dialog-content" />')
      .append(cj('<pre class="notiflog-code" />').text(pretty));

    $content.dialog({
      title: 'Log #' + String(id) + ' context',
      modal: true,
      width: Math.min(cj(window).width() - 120, 980),
      height: Math.min(cj(window).height() - 120, 700),
      buttons: [{
        text: 'Close',
        click: function () { cj(this).dialog('close'); }
      }],
      close: function () { cj(this).dialog('destroy').remove(); }
    });
  }

  function copyToClipboard(text) {
    var val = typeof text === 'string' ? text : toPretty(text);
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(val);
      return;
    }
    var $tmp = cj('<textarea>').css({position: 'fixed', top: -1000, left: -1000})
      .val(val).appendTo('body').select();
    document.execCommand('copy');
    $tmp.remove();
  }

  cj(function () {
    cj(document).on('click', '.notiflog-view', function () {
      openContextDialog(cj(this).data('full'), cj(this).data('id'));
    });
    cj(document).on('click', '.notiflog-copy', function () {
      copyToClipboard(cj(this).data('full'));
    });
  });
})(CRM.$);
