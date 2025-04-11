<?php

declare(strict_types = 1);

namespace Civi\Notification;

use Civi\Notification\Data\NotificationRecipient;

interface NotificationSenderInterface {

  /**
   * @phpstan-param array<string, mixed> $tokenContext
   */
  public function sendNotification(int $msgTemplateId, NotificationRecipient $recipient, array $tokenContext): void;

}
