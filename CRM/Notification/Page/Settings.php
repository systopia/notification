<?php
declare(strict_types = 1);

class CRM_Notification_Page_Settings extends CRM_Core_Page {

  public function run() {

    $wrapper = new CRM_Utils_Wrapper();
    return $wrapper->run('CRM_Notification_Form_Settings', ts('Notification Settings'));
  }

}
