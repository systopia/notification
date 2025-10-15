<?php
declare(strict_types = 1);

/**
 * @method void setDefaults(array $defaults)
 * @method array exportValues()
 */
class CRM_Notification_Form_Settings extends CRM_Core_Form {

  public function buildQuickForm(): void {
    $this->add('checkbox', 'notification_queue_job_enabled', ts('Notifications enabled'));
    $this->add(
      'select',
      'notification_processing_mode',
      ts('Processing mode'),
      ['queue' => 'queue', 'event' => 'event'],
      TRUE
    );

    $this->addButtons([
      ['type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => ts('Cancel')],
    ]);

    $this->setDefaults($this->loadDefaults());
    parent::buildQuickForm();
  }

  /**
   * @return array<string,mixed>
   */
  protected function loadDefaults(): array {
    $enabled = (bool) Civi::settings()->get('notification_queue_job_enabled');

    $modeRaw = Civi::settings()->get('notification_processing_mode');
    $mode = (is_string($modeRaw) && $modeRaw !== '') ? $modeRaw : 'queue';

    return [
      'notification_queue_job_enabled' => $enabled,
      'notification_processing_mode'   => $mode,
    ];
  }

  public function postProcess(): void {
    $v = $this->exportValues();

    Civi::settings()->set(
      'notification_queue_job_enabled',
      (bool) ($v['notification_queue_job_enabled'] ?? FALSE)
    );

    Civi::settings()->set(
      'notification_processing_mode',
      (string) ($v['notification_processing_mode'] ?? 'queue')
    );

    CRM_Core_Session::setStatus(ts('Settings saved'), '', 'success');
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/notification/entities', 'reset=1'));
  }

}
