<?php
declare(strict_types = 1);

namespace Civi\Notification\Support;

use Civi\Notification\Queue\DrainingQueueInterface;

final class InMemoryQueue implements DrainingQueueInterface {
  /**
   * @var array<int, array<mixed>> */
  private array $payloads = [];

  /**
   * @param array<mixed> $payload
   */
  public function enqueue(array $payload): void {
    $this->payloads[] = $payload;
  }

  /**
   * @param callable(array<string, mixed>): void $consumer
   */
  public function drain(callable $consumer): int {
    $count = 0;
    while ($this->payloads !== []) {
      /** @var array<mixed> $payload */
      $payload = array_shift($this->payloads);
      $consumer($payload);
      $count++;
    }
    return $count;
  }

  /**
   * @return array<int, array<mixed>>
   */
  public function all(): array {
    return $this->payloads;
  }

}
