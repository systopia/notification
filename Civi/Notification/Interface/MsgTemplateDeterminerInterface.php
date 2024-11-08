<?php

declare(strict_types = 1);

namespace Civi\Notification\Interface;

use Civi\Notification\Entity\NotificationRecipient;
use Civi\Notification\Entity\RuleMsgTemplateEntity;

interface MsgTemplateDeterminerInterface {

  /**
   * @param \Civi\Notification\Entity\NotificationRecipient $recipient
   * @param array<int, RuleMsgTemplateEntity> $messageTemplates
   * @return int|null
   */
  public function determineMessageTemplateId(NotificationRecipient $recipient, array $messageTemplates): ?int;

}
