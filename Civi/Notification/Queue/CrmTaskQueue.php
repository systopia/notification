<?php
declare(strict_types = 1);

namespace Civi\Notification\Queue;

use Civi\Notification\Support\QueueCallbacks;

/**
 */
final class CrmTaskQueue implements QueueInterface {
  private string $name;
  private string $type;

  public function __construct(string $name = 'notification.jobs', string $type = 'Sql') {
    $this->name = $name;
    $this->type = $type;
  }

  /**
   *
   * @param \Civi\Notification\DTO\NotificationEvent $event
   */
  public function enqueueEvent(\Civi\Notification\DTO\NotificationEvent $event): void {

    $payload = \method_exists($event, 'toArray')
      ? $event->toArray()
      : (array) $event;

    $this->enqueuePayload($payload);
  }

  /**
   * /**
   * @param array<string,mixed> $payload */
  public function enqueue(array $payload): void {
    $this->enqueuePayload($payload);
  }

  /**
   * @param array<string,mixed> $payload */
  public function push(array $payload): void {
    $this->enqueuePayload($payload);
  }

  /**
   * @param object|array<string,mixed> $event
   */
  public function enqueueGeneric($event): void {
    if (\is_array($event)) {
      $payload = $event;
    }
    elseif (\is_object($event) && \method_exists($event, 'toArray')) {
      /** @var array<string,mixed> $payload */
      $payload = $event->toArray();
    }
    else {
      /** @var array<string,mixed> $payload */
      $payload = (array) $event;
    }
    $this->enqueuePayload($payload);
  }

  /**
   * /**
   * @param array<string,mixed> $payload */
  private function enqueuePayload(array $payload): void {
    $svc = new \CRM_Queue_Service();
    $queue = $svc->load([
      'type'  => $this->type,
      'name'  => $this->name,
      'reset' => FALSE,
    ]);

    $task = new \CRM_Queue_Task(
      [QueueCallbacks::class, 'handle'],
      [$payload],
      'notification: event'
    );

    $queue->createItem($task);
  }

}
