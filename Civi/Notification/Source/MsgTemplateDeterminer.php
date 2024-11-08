<?php

declare(strict_types = 1);

namespace Civi\Notification\Source;

use Civi\Notification\Entity\NotificationRecipient;
use Civi\Notification\Interface\MsgTemplateDeterminerInterface;

class MsgTemplateDeterminer implements MsgTemplateDeterminerInterface {

  public function determineMessageTemplateId(NotificationRecipient $recipient, array $messageTemplates): ?int {
    foreach ($messageTemplates as $messageTemplate) {
      if (in_array($recipient->getPreferredLanguage(), $messageTemplate->getLanguages(), TRUE)) {
        return $messageTemplate->getMsgTemplateId();
      }
    }

    return NULL;
  }

}
