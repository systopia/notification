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
    $rid   = $rule->getId();
    $title = method_exists($rule, 'getTitle') ? (string) $rule->getTitle() : '';

    DbLogger::log('debug', 'rule.match-check', 'Checking rule match', [
      'rule_id' => $rid,
      'title'   => $title,
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
          'rule_id'   => $rid,
          'exception' => $e,
        ]);
        throw $e;
      }
      return TRUE;
    }
    return FALSE;
  }

  private function sendNotifications(RuleEntity $rule, NotificationContext $context): void {
    $rid = $rule->getId();

    $tokenContext = $this->tokenContextGenerator->generateTokenContext($rule, $context);
    $recipients   = $this->getNotificationRecipients($rule);

    DbLogger::log('info', 'rule.recipients', 'Recipients resolved', [
      'rule_id' => $rid,
      'count'   => count($recipients),
    ]);

    foreach ($recipients as $recipient) {
      /** @var int|null $templateId */
      $templateId = $this->messageTemplateDeterminer->determineMessageTemplateId(
        $recipient,
        $rule->getMsgTemplates()
      );
      $tid = is_int($templateId) ? $templateId : 0;

      DbLogger::log('debug', 'rule.send.start', 'Sending to recipient', [
        'rule_id'     => $rid,
        'template_id' => $tid,
      ]);

      $this->notificationSender->sendNotification($tid, $recipient, $tokenContext);

      DbLogger::log('debug', 'rule.send.done', 'Sent to recipient', [
        'rule_id'     => $rid,
        'template_id' => $tid,
      ]);
    }
  }

  /**
   * @return list<NotificationRecipient>
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  private function getNotificationRecipients(RuleEntity $rule): array {
    // phpcs:enable

    $emails = $rule->getEmailAddresses();
    if ($emails !== NULL) {
      $out = [];
      foreach ((array) $emails as $val) {
        if (is_string($val)) {
          $email = trim($val);
          if ($email !== '') {
            $out[] = new NotificationRecipient($email, NULL);
            continue;
          }
        }

        $contactId = NULL;
        if (is_int($val)) {
          $contactId = $val;
        }
        elseif (is_string($val) && $val !== '' && ctype_digit($val)) {
          $contactId = (int) $val;
        }

        if ($contactId !== NULL && $contactId > 0) {
          try {
            $emailRow = \Civi\Api4\Email::get(FALSE)
              ->addWhere('contact_id', '=', $contactId)
              ->addOrderBy('is_primary', 'DESC')->addOrderBy('id', 'DESC')
              ->addSelect('email')
              ->setLimit(1)
              ->execute()
              ->single();

            $email = $emailRow['email'] ?? NULL;

            if (!is_string($email) || $email === '') {
              DbLogger::log('warning', 'rule.recipients', 'No email for contact_id in emailAddresses', [
                'contact_id' => $contactId,
              ]);
            }
            else {
              $cRow = \Civi\Api4\Contact::get(FALSE)
                ->addWhere('id', '=', $contactId)
                ->addSelect('display_name', 'preferred_language')
                ->setLimit(1)
                ->execute()
                ->single();

              $displayName = $cRow['display_name'] ?? '';
              $display = is_string($displayName) ? $displayName : '';
              $prefLang = isset($cRow['preferred_language']) && is_string($cRow['preferred_language'])
                ? $cRow['preferred_language'] : NULL;

              /** @var array{id:int,display_name:string,email:string,preferred_language:?string} $cd */
              $cd = [
                'id' => $contactId,
                'display_name' => $display,
                'email' => $email,
                'preferred_language' => $prefLang,
              ];

              $out[] = new NotificationRecipient($email, $cd);

              DbLogger::log('debug', 'rule.recipients', 'Resolved email from contact_id', [
                'contact_id' => $contactId,
                'email' => $email,
              ]);
            }
          }
          catch (\Throwable $e) {
            DbLogger::log('warning', 'rule.recipients', 'Lookup primary email failed (emailAddresses contact_id)', [
              'contact_id' => $contactId,
              'exception'  => $e,
            ]);
            throw $e;
          }
        }
      }
      /** @var list<NotificationRecipient> $out */
      return $out;
    }

    $recipients = [];
    foreach ($rule->getContactSelections() as $sel) {
      $got = $this->contactLoader->getContacts($sel, $rule->getPreferredLocationTypeId());
      $recipients = array_merge($recipients, $got);
    }
    /** @var list<NotificationRecipient> $recipients */
    return $recipients;
  }

}
