<?php
declare(strict_types = 1);

/**
 */
class CRM_Notification_Form_Settings extends CRM_Core_Form {

  public function buildQuickForm() {
    $this->add('checkbox', 'notification_queue_job_enabled', ts('Notifications enabled'));
    $this->add('select', 'notification_processing_mode',
      ts('Processing mode'), ['queue' => 'queue', 'event' => 'event'], TRUE);
    $this->addButtons([
      ['type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => ts('Cancel')],
    ]);
    $this->setDefaults($this->loadDefaults());
    parent::buildQuickForm();
  }

  protected function loadDefaults() {
    return [
      'notification_queue_job_enabled' =>
      (bool) Civi::settings()->get('notification_queue_job_enabled'),
      'notification_processing_mode' =>
      Civi::settings()->get('notification_processing_mode') ?: 'queue',
    ];
  }

  public function postProcess() {
    $v = $this->exportValues();
    Civi::settings()->set('notification_queue_job_enabled', !empty($v['notification_queue_job_enabled']));
    Civi::settings()->set('notification_processing_mode', $v['notification_processing_mode']);
    CRM_Core_Session::setStatus(ts('Settings saved'), '', 'success');
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/notification/entities', 'reset=1'));
  }

}
