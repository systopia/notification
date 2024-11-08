<?php

declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Entity\NotificationRecipient;
use Civi\Notification\Entity\RuleEntity;
use Civi\Notification\Interface\RuleHandlerInterface;
use Civi\Notification\Source\ContactLoader;
use Civi\Notification\Source\MsgTemplateDeterminer;
use Civi\Notification\Source\NotificationSender;

class RuleHandler implements RuleHandlerInterface {

  private ConditionHandler $conditionHandler;
  private FieldMonitoringHandler $fieldMonitorHandler;
  private NotificationSender $notificationSender;
  private ContactLoader $contactLoader;
  private MsgTemplateDeterminer $msgTemplateDeterminer;

  public function __construct() {
    $this->conditionHandler = new ConditionHandler();
    $this->fieldMonitorHandler = new FieldMonitoringHandler();
    $this->notificationSender = new NotificationSender();
    $this->contactLoader = new ContactLoader();
    $this->msgTemplateDeterminer = new MsgTemplateDeterminer();
  }

  public function evaluateRule(RuleEntity $rule, array $newValues, array $oldValues): bool {
    foreach ($rule->getConditions() as $condition) {
      if (!$this->conditionHandler->evaluateCondition($condition, $newValues)) {
        return FALSE;
      }
    }

    foreach ($rule->getFieldMonitorings() as $fieldMonitoring) {
      if (!$this->fieldMonitorHandler->evaluate($fieldMonitoring, $newValues, $oldValues)) {
        return FALSE;
      }
    }

    // TODO: add logic for sending notification
    $this->sendNotification($rule);

    return TRUE;
  }

  private function sendNotification(RuleEntity $rule): void {
    foreach ($this->getNotificationRecipients($rule) as $recipient) {
      $msgId = $this->msgTemplateDeterminer->determineMessageTemplateId($recipient, $rule->getMsgTemplates());

      if ($msgId != NULL) {
        $this->notificationSender->sendNotification($msgId, $recipient, []);
      }
    }
  }

  /**
   * @param \Civi\Notification\Entity\RuleEntity $rule
   * @return array<int, NotificationRecipient>
   */
  private function getNotificationRecipients(RuleEntity $rule): array {
    $recipients = [];

    if ($rule->getEmailAddresses() !== NULL) {
      $recipients = $this->contactLoader->getContactsByRuleEmails($rule);
    }
    else {
      foreach ($rule->getContactSelections() as $contactSelection) {
        $recipients = array_merge($recipients, $this->contactLoader->getContacts($contactSelection));
      }
    }

    return $recipients;
  }

}
