<?php
declare(strict_types = 1);

namespace Civi\Notification\Event;

use Civi\Notification\DTO\Change;
use Civi\Notification\DTO\ChangeSet;
use Civi\Notification\DTO\NotificationEvent;

class EventFactory implements EventFactoryInterface {

  public function __construct(private \Civi\Notification\Snapshot\SnapshotStore $snapshots) {}

  public function fromHook(string $op,
    string $entity,
    int|string|null $id,
    array $before,
    array $after,
    array $context = []): ?NotificationEvent {
    $changes = new ChangeSet();
    foreach ($after as $k => $v) {
      $b = $before[$k] ?? NULL;
      if ($b !== $v) {
        $changes->add(new Change($k, $b, $v));
      }
    }
    if (count($changes) === 0 && $op !== 'delete') {
      return NULL;
    }
    return new NotificationEvent($entity, $op, $id, $before, $after, $changes, $context);
  }

}
