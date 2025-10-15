(function($, CRM) {
  $(function() {

    $(document).on('click', '[data-action]', function () {
      var action = $(this).data('action');
      var id = $(this).data('id');
      var $store = $('#json-' + id);
      var json = $store.length ? $store.val() : '';

      if (action === 'view') {
        var $dlg = $('<div/>').append($('<pre/>', { text: json }));
        $dlg.dialog({
          title: 'Context',
          modal: true,
          width: 900,
          close: function() { $(this).dialog('destroy').remove(); },
          buttons: [{
            text: 'Close',
            click: function() { $(this).dialog('close'); }
          }]
        });
      }

      if (action === 'copy') {
        var ok = false;

        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(json).then(function(){}, function(){});
          ok = true;
        } else {
          var $ta = $('<textarea/>').css({position:'fixed',left:'-9999px',top:'0'}).val(json).appendTo('body').select();
          try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
          $ta.remove();
        }

        CRM.alert(ok ? 'Copied to clipboard' : 'Could not copy', 'Copy', ok ? 'info' : 'error', { expires: 2000 });
      }
    });

  });
})(CRM.$, CRM);
