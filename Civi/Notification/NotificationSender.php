<?php

declare(strict_types = 1);

namespace Civi\Notification;

use Civi\Notification\Data\NotificationRecipient;

class NotificationSender implements NotificationSenderInterface {

  public function sendNotification(int $msgTemplateId, NotificationRecipient $recipient, array $tokenContext): void {
    $tokenContext['contactId'] = $recipient->getContactId();

    [$fromName, $fromEmail] = \CRM_Core_BAO_Domain::getNameAndEmail();

    $sendTemplateParams = [
      'messageTemplateID' => $msgTemplateId,
      'from' => ($fromName ?? '') . ' <' . $fromEmail . '>',
      'toName' => $recipient->getName(),
      'toEmail' => $recipient->getEmail(),
      'tokenContext' => $tokenContext,
    ];

    \CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
  }

}
