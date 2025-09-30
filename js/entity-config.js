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

    $(document).on('click', '.js-toggle-rules', function (e) {
      e.preventDefault();
      var $btn = $(this);
      var target = $btn.attr('data-target');
      var $panel = $(target);
      if (!$panel.length) return;

      var wasHidden = $panel.hasClass('is-hidden');
      $panel.stop(true, true);

      if (wasHidden) {
          $panel.removeClass('is-hidden').hide().slideDown(140, function () {
          $panel.attr('aria-hidden', 'false');
        });
        $btn.attr('aria-expanded', 'true')
          .text(CRM.ts('notification')('Hide rules'));
      } else {
         $panel.slideUp(140, function () {
          $panel.addClass('is-hidden').attr('aria-hidden', 'true');
        });
        var count = $btn.data('count') || '';
        $btn.attr('aria-expanded', 'false')
          .text(count ? CRM.ts('notification')('Show %1 rules').replace('%1', count)
            : CRM.ts('notification')('Show rules'));
      }
    });

    $('.crm-accordion-wrapper .crm-accordion-header').on('click', function (e) {
      if ($(e.target).closest('button,a').length) return;
      $(this).next('.crm-accordion-body').slideToggle(140);
      $(this).parent().toggleClass('is-open');
    });
  });
})(CRM.$, CRM);
