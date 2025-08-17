<?php
declare(strict_types = 1);

namespace Civi\Notification\Queue;

interface DrainingQueueInterface extends QueueInterface {

  /**
   *
   * @param callable(array<string,mixed>):void $consumer
   * @return int
   */
  public function drain(callable $consumer): int;

}
