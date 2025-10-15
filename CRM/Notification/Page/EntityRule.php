<?php
declare(strict_types = 1);

class CRM_Notification_Page_EntityRule extends CRM_Core_Page {

  public function run(): void {
    $entity = CRM_Utils_Request::retrieve('entity_type', 'String', $this, FALSE, 'Activity');

    $controller = new CRM_Core_Controller_Simple(
      'CRM_Notification_Form_EntityRuleWizard',
      FALSE,
      NULL
    );

    CRM_Utils_System::setTitle(ts('New Notification Rule'));

    $controller->set('entity_type', $entity);

    $controller->process();
    $controller->run();
  }

}
