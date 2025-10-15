<?php
declare(strict_types = 1);

namespace Civi\Notification\Mailer;

use Civi\Notification\Data\NotificationRecipient;
use Civi\Notification\NotificationSenderInterface;
use Civi\Notification\Support\DbLogger;

final class BasicNotificationSender implements NotificationSenderInterface {

  // phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded
  public function sendNotification(
    // phpcs:enable
    int $msgTemplateId,
    NotificationRecipient $recipient,
    array $tokenContext = []
  ): void {
    $email = $recipient->getEmail();
    if ($email === '') {
      DbLogger::log('warning', 'send.email.skip', 'Recipient without email', []);
      return;
    }

    $fromSetting = \Civi::settings()->get('from_email_address');
    $from = (is_string($fromSetting) && $fromSetting !== '') ? $fromSetting : 'Notifications <test@example.org>';

    $contactIdRaw = $recipient->getContactId();
    $contactId = is_int($contactIdRaw) ? $contactIdRaw : NULL;

    $activityId = $tokenContext['activityId'] ?? NULL;
    if ($activityId === NULL && isset($tokenContext['activity']) && is_array($tokenContext['activity'])) {
      $activityId = $tokenContext['activity']['id'] ?? NULL;
    }
    if ($activityId === NULL && isset($tokenContext['after']) && is_array($tokenContext['after'])) {
      $activityId = $tokenContext['after']['id'] ?? NULL;
    }
    if ($activityId === NULL) {
      $activityId = $tokenContext['entity_id'] ?? NULL;
    }

    if ($msgTemplateId > 0) {
      try {
        $params = [
          'messageTemplateID' => $msgTemplateId,
          'contactId'         => $contactId,
          'activityId'        => $activityId,
          'from'              => $from,
          'toEmail'           => $email,
          'isTest'            => FALSE,
          'tplParams'         => array_merge($tokenContext, [
            'activityId' => $activityId,
          ]),
        ];

        DbLogger::log('debug', 'send.email.template.params', 'About to send with MessageTemplate', [
          'template_id' => $msgTemplateId,
          'contactId'   => $contactId,
          'activityId'  => $activityId,
          'to'          => $email,
        ]);

        $result = \CRM_Core_BAO_MessageTemplate::sendTemplate($params);

        $isError = isset($result['is_error']) && (bool) $result['is_error'];
        if ($isError) {
          DbLogger::log('error', 'send.email.template.error', 'sendTemplate returned error', [
            'template_id' => $msgTemplateId,
            'error'       => $result['is_error'],
          ]);
        }
        else {
          DbLogger::log('info', 'send.email.done', 'Sent email via template', [
            'template_id' => $msgTemplateId,
            'to'          => $email,
          ]);
        }
        return;
      }
      catch (\Throwable $e) {
        DbLogger::log('error', 'send.email.compose.error', 'Template send failed', [
          'template_id' => $msgTemplateId,
          'exception'   => ['type' => get_class($e), 'message' => $e->getMessage()],
        ]);
        throw $e;
      }
    }

    // ---- Fallback sin plantilla ----
    $subject = NULL;
    $html    = NULL;
    $text    = NULL;

    if (isset($tokenContext['subject']) && is_string($tokenContext['subject'])) {
      $subject = $tokenContext['subject'];
    }
    if (array_key_exists('html', $tokenContext) && is_string($tokenContext['html'])) {
      $html = $tokenContext['html'];
    }
    if (array_key_exists('text', $tokenContext) && is_string($tokenContext['text'])) {
      $text = $tokenContext['text'];
    }

    $subject = $subject ?? 'Notification';
    if ($html === NULL && $text === NULL) {
      $text = 'You have a new notification.';
    }

    $params = [
      'from'    => $from,
      'toEmail' => $email,
      'subject' => $subject,
    ];
    if ($html !== NULL) {
      $params['html'] = $html;
    }
    if ($text !== NULL) {
      $params['text'] = $text;
    }

    DbLogger::log('info', 'send.email', 'Sending email (fallback)', [
      'to'      => $email,
      'subject' => $subject,
    ]);

    \CRM_Utils_Mail::send($params);

    DbLogger::log('info', 'send.email.done', 'Sent email (fallback)', [
      'to'      => $email,
      'subject' => $subject,
    ]);
  }

  /**
   * @param list<NotificationRecipient> $recipients
   * @param array<string,mixed> $tokenContext
   */
  public function send(
    array $recipients,
    string $subject,
    ?string $html,
    ?string $text,
    ?int $msgTemplateId = NULL,
    array $tokenContext = []
  ): void {
    foreach ($recipients as $r) {
      if (!$r instanceof NotificationRecipient) {
        continue;
      }
      $ctx = $tokenContext;
      $ctx['subject'] = $ctx['subject'] ?? $subject;
      $ctx['html']    = array_key_exists('html', $ctx) ? $ctx['html'] : $html;
      $ctx['text']    = array_key_exists('text', $ctx) ? $ctx['text'] : $text;

      $this->sendNotification($msgTemplateId ?? 0, $r, $ctx);
    }
  }

}
