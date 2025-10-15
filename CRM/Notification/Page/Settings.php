<?php
declare(strict_types = 1);

class CRM_Notification_Page_Settings extends CRM_Core_Page {

  public function run(): void {

    CRM_Utils_System::setTitle(ts('Notification Settings'));

    $wrapper = new CRM_Utils_Wrapper();
    $wrapper->run('CRM_Notification_Form_Settings', ts('Notification Settings'));

    parent::run();
  }

}
