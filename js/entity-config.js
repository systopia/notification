(function ($, CRM) {
  $(function () {
    var $select = $('#notification-entity-select');
    var $btn = $('#notification-create-rule');

    function toggleButton() {
      $btn.prop('disabled', !$select.val());
    }

    $select.on('change', toggleButton);
    toggleButton();

    $btn.on('click', function (e) {
      e.preventDefault();
      var entity = $select.val();
      if (!entity) return;
      var url = CRM.url('civicrm/notification/entity-rule', { reset: 1, entity_type: entity });
      window.location.href = url;
    });

    $('.js-toggle-rules').on('click', function (e) {
      e.preventDefault();
      var id = $(this).data('ruleset');
      $('#ruleset-rules-' + id).toggleClass('is-hidden');
    });
  });
})(CRM.$, CRM);
