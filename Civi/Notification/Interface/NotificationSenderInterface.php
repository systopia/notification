<?php

declare(strict_types = 1);

namespace Civi\Notification\Interface;

use Civi\Notification\Entity\NotificationRecipient;

interface NotificationSenderInterface {

  /**
   * @phpstan-param array<string, mixed> $tokenContext
   */
  public function sendNotification(int $msgTemplateId, NotificationRecipient $recipient, array $tokenContext): void;

}
