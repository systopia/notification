<?php
declare(strict_types = 1);

namespace Civi\Notification\Queue;

interface QueueInterface {

  public function enqueue(array $payload): void;

}
