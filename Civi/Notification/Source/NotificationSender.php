<?php

declare(strict_types = 1);

namespace Civi\Notification\Source;

use Civi\Notification\Entity\NotificationRecipient;
use Civi\Notification\Interface\NotificationSenderInterface;

class NotificationSender implements NotificationSenderInterface {

  public function sendNotification(int $msgTemplateId, NotificationRecipient $recipient, array $tokenContext): void {
    $tokenContext['contactId'] = $recipient->getId();

    [$fromName, $fromEmail] = \CRM_Core_BAO_Domain::getNameAndEmail();

    $sendTemplateParams = [
      'messageTemplateID' => $msgTemplateId,
      'from' => ($fromName ?? '') . ' <' . $fromEmail . '>',
      'toName' => $recipient->getDisplayName(),
      'toEmail' => $recipient->getEmail(),
      'tokenContext' => $tokenContext,
    ];

    \CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
  }

}
