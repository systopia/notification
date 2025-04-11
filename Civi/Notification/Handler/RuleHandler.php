<?php

declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Data\NotificationRecipient;
use Civi\Notification\Entity\RuleEntity;
use Civi\Notification\EntityService\ContactLoaderInterface;
use Civi\Notification\MsgTemplateDeterminerInterface;
use Civi\Notification\NotificationSenderInterface;
use Civi\Notification\TokenContextGeneratorInterface;

final class RuleHandler implements RuleHandlerInterface {

  public function __construct(
    private RuleMatchCheckerInterface $ruleMatchChecker,
    private NotificationSenderInterface $notificationSender,
    private ContactLoaderInterface $contactLoader,
    private MsgTemplateDeterminerInterface $messageTemplateDeterminer,
    private TokenContextGeneratorInterface $tokenContextGenerator
  ) {}

  public function evaluateRule(RuleEntity $rule, NotificationContext $context): bool {
    if ($this->ruleMatchChecker->isRuleMatched($rule, $context)) {
      $this->sendNotifications($rule, $context);

      return TRUE;
    }

    return FALSE;
  }

  private function sendNotifications(RuleEntity $rule, NotificationContext $context): void {
    $tokenContext = $this->tokenContextGenerator->generateTokenContext($rule, $context);

    foreach ($this->getNotificationRecipients($rule) as $recipient) {
      $templateId = $this->messageTemplateDeterminer->determineMessageTemplateId($recipient, $rule->getMsgTemplates());

      if ($templateId != NULL) {
        $this->notificationSender->sendNotification($templateId, $recipient, $tokenContext);
      }
    }
  }

  /**
   * @return list<NotificationRecipient>
   */
  private function getNotificationRecipients(RuleEntity $rule): array {
    if ($rule->getEmailAddresses() !== NULL) {
      return array_map(fn (string $email) => new NotificationRecipient($email), $rule->getEmailAddresses());
    }

    $recipients = [];
    foreach ($rule->getContactSelections() as $contactSelection) {
      $recipients = array_merge($recipients, $this->contactLoader->getContacts($contactSelection, $rule->getPreferredLocationTypeId()));
    }

    return $recipients;
  }

}
