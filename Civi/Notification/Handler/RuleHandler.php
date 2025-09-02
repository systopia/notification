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
use Civi\Notification\Support\DbLogger;

final class RuleHandler implements RuleHandlerInterface {

  public function __construct(
    private RuleMatchCheckerInterface $ruleMatchChecker,
    private NotificationSenderInterface $notificationSender,
    private ContactLoaderInterface $contactLoader,
    private MsgTemplateDeterminerInterface $messageTemplateDeterminer,
    private TokenContextGeneratorInterface $tokenContextGenerator
  ) {}

  public function evaluateRule(RuleEntity $rule, NotificationContext $context): bool {
    $rid   = method_exists($rule, 'getId') ? $rule->getId() : NULL;
    $title = method_exists($rule, 'getTitle') ? $rule->getTitle() : NULL;

    DbLogger::log('debug', 'rule.match-check', 'Checking rule match', [
      'rule_id' => $rid,
      'title' => $title,
    ]);

    $matched = $this->ruleMatchChecker->isRuleMatched($rule, $context);

    DbLogger::log($matched ? 'info' : 'debug', 'rule.match-result',
      $matched ? 'Rule matched' : 'Rule not matched',
      ['rule_id' => $rid]
    );

    if ($matched) {
      try {
        $this->sendNotifications($rule, $context);
      }
      catch (\Throwable $e) {
        DbLogger::log('error', 'rule.send', 'Exception while sending notifications', [
          'rule_id' => $rid,
          'exception' => $e,
        ]);
      }
      return TRUE;
    }
    return FALSE;
  }

  private function sendNotifications(RuleEntity $rule, NotificationContext $context): void {
    $rid = method_exists($rule, 'getId') ? $rule->getId() : NULL;

    $tokenContext = $this->tokenContextGenerator->generateTokenContext($rule, $context);
    $recipients   = $this->getNotificationRecipients($rule);

    DbLogger::log('info', 'rule.recipients', 'Recipients resolved', [
      'rule_id' => $rid,
      'count' => count($recipients),
    ]);

    foreach ($recipients as $recipient) {
      $templateId = $this->messageTemplateDeterminer->determineMessageTemplateId(
        $recipient, $rule->getMsgTemplates()
      );

      DbLogger::log('debug', 'rule.send.start', 'Sending to recipient', [
        'rule_id' => $rid,
        'template_id' => $templateId,
      ]);

      $this->notificationSender->sendNotification((int) ($templateId ?? 0), $recipient, $tokenContext);

      DbLogger::log('debug', 'rule.send.done', 'Sent to recipient', [
        'rule_id' => $rid,
        'template_id' => $templateId,
      ]);
    }
  }

  /**
   * @return list<NotificationRecipient> */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  private function getNotificationRecipients(RuleEntity $rule): array {
  // phpcs:enable

    $emails = $rule->getEmailAddresses();
    if ($emails !== NULL) {
      $out = [];
      foreach ((array) $emails as $val) {
        if (is_string($val) && $val !== '') {

          $out[] = new NotificationRecipient($val, NULL);
        }
        elseif (is_int($val) && $val > 0) {

          try {
            $emailRow = \Civi\Api4\Email::get(FALSE)
              ->addWhere('contact_id', '=', $val)
              ->addOrderBy('is_primary', 'DESC')->addOrderBy('id', 'DESC')
              ->addSelect('email')
              ->setLimit(1)
              ->execute()
              ->single();

            $email = is_string($emailRow['email'] ?? NULL) ? $emailRow['email'] : NULL;

            if ($email) {
              $cRow = \Civi\Api4\Contact::get(FALSE)
                ->addWhere('id', '=', $val)
                ->addSelect('display_name', 'preferred_language')
                ->setLimit(1)
                ->execute()
                ->single();

              /** @var array{id:int,display_name:string,email:string,preferred_language:?string} $cd */
              $cd = [
                'id' => $val,
                'display_name' => (string) ($cRow['display_name'] ?? ''),
                'email' => $email,
                'preferred_language' => $cRow['preferred_language'] ?? NULL,
              ];

              $out[] = new NotificationRecipient($email, $cd);

              DbLogger::log('debug', 'rule.recipients', 'Resolved email from contact_id', [
                'contact_id' => $val,
                'email' => $email,
              ]);
            }
            else {
              DbLogger::log('warning', 'rule.recipients', 'No email for contact_id in emailAddresses', [
                'contact_id' => $val,
              ]);
            }
          }
          catch (\Throwable $e) {
            DbLogger::log('warning', 'rule.recipients', 'Lookup primary email failed (emailAddresses int)', [
              'contact_id' => $val,
              'exception' => $e,
            ]);
          }
        }
      }
      return $out;
    }

    $recipients = [];
    foreach ($rule->getContactSelections() as $sel) {
      $got = $this->contactLoader->getContacts($sel, $rule->getPreferredLocationTypeId());
      $recipients = array_merge($recipients, $got);
    }
    return $recipients;
  }

}
