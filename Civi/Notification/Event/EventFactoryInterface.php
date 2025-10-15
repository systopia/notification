<?php
declare(strict_types = 1);

namespace Civi\Notification\Event;

use Civi\Notification\DTO\NotificationEvent;

interface EventFactoryInterface {

  public function fromHook(string $op,
    string $entity,
    int|string|null $id,
    array $before,
    array $after,
    array $context = []): ?NotificationEvent;

}
