<?php
declare(strict_types = 1);

namespace Civi\Notification\Support;

final class QueueCallbacks {

  /**
   * @param \CRM_Queue_TaskContext $ctx
   * @param array<string,mixed> $payload
   */
  public static function handle(\CRM_Queue_TaskContext $ctx, array $payload): bool {
    /** @var \Civi\Notification\Runner\QueueRunner $runner */
    $runner = \Civi::service('notification.runner');
    $runner->handle($payload);
    return TRUE;
  }

}
