{crmScope extensionKey='notification'}
<div class="crm-block crm-form-block">
  <table class="form-layout">
    <tr><td class="label">{$form.notification_queue_job_enabled.label}</td><td class="content">{$form.notification_queue_job_enabled.html}</td></tr>
    <tr><td class="label">{$form.notification_processing_mode.label}</td><td class="content">{$form.notification_processing_mode.html}</td></tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{/crmScope}
