<?php

declare(strict_types = 1);

namespace Civi\Notification;

use Civi\Notification\Data\NotificationRecipient;

interface MsgTemplateDeterminerInterface {

  /**
   * @param list<\Civi\Notification\Entity\RuleMsgTemplateEntity> $messageTemplates
   */
  public function determineMessageTemplateId(NotificationRecipient $recipient, array $messageTemplates): ?int;

}
