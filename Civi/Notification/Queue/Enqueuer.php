<?php
declare(strict_types = 1);

namespace Civi\Notification\Queue;

use Civi\Notification\DTO\NotificationEvent;

class Enqueuer {

  public function __construct(private QueueInterface $queue) {}

  public function enqueueEvent(NotificationEvent $event): void {
    $payload = [
      'type' => 'notification.event',
      'entity' => $event->entity,
      'op' => $event->op,
      'id' => $event->id,
      'before' => $event->before,
      'after' => $event->after,
      'changes' => array_map(
        fn($c) => [
          'field' => $c->field,
          'before' => $c->before,
          'after' => $c->after,
        ],
        $event->changes->all()
      ),
      'context' => $event->context,
      'ts' => time(),
    ];
    $this->queue->enqueue($payload);
  }

}
