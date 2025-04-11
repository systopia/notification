<?php

declare(strict_types = 1);

namespace Civi\Notification;

use Civi\Notification\Data\NotificationRecipient;

class MsgTemplateDeterminer implements MsgTemplateDeterminerInterface {

  public function determineMessageTemplateId(NotificationRecipient $recipient, array $messageTemplates): ?int {
    $preferredLanguage = $recipient->getPreferredLanguage();

    $fallbackTemplate = NULL;
    foreach ($messageTemplates as $messageTemplate) {
      if ([] === $messageTemplate->getLanguages()) {
        $fallbackTemplate = $messageTemplate;
        if (NULL === $preferredLanguage) {
          break;
        }
      }

      if (in_array($preferredLanguage, $messageTemplate->getLanguages(), TRUE)) {
        return $messageTemplate->getMsgTemplateId();
      }
    }

    return $fallbackTemplate?->getMsgTemplateId();
  }

}
