<?php
declare(strict_types = 1);

namespace Civi\Notification\Queue;

class CiviQueue implements QueueInterface {
  private string $name = 'notification.jobs';

  /**
   * @param array<string, mixed> $payload */
  public function enqueue(array $payload): void {
    $queue = \Civi::queue($this->name);
    $queue->createItem($payload);
  }

}
