/* templates/CRM/Notification/Page/queue.js */
(function (cj) {
  'use strict';

  cj(function () {
    cj(document).on('click', '[data-action]', function () {
      var action = cj(this).data('action');
      var id = cj(this).data('id');
      var $store = cj('#payload-' + id);
      var json = $store.length ? ($store.val() || '') : '';

      if (action === 'view') {
        var $dlg = cj('<div/>').append(
          cj('<pre/>', { text: json, 'class': 'notifq-full' })
        );
        $dlg.dialog({
          title: 'Payload',
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

      if (action === 'copy') {
        var ok = false;
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(json).then(function(){}, function(){});
          ok = true;
        } else {
          var $ta = cj('<textarea/>').css({position:'fixed',left:'-9999px',top:'0'})
            .val(json).appendTo('body').select();
          try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
          $ta.remove();
        }
        if (window.CRM && CRM.alert) {
          CRM.alert(ok ? 'Copied to clipboard' : 'Could not copy', 'Copy', ok ? 'info' : 'error', { expires: 2000 });
        } else {
          alert(ok ? 'Copied' : 'Could not copy');
        }
      }
    });
  });
})(CRM.$);
