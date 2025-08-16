<?php
declare(strict_types = 1);

namespace Civi\Notification\Snapshot;

class SnapshotStore {
  /**
   * @var array<string, array<string, mixed>> */
  private array $store = [];

  /**
   * @param array<string, mixed> $before */
  public function put(string $entity, string $op, int|string|null $id, array $before): void {
    $key = $this->key($entity, $op, $id);
    $this->store[$key] = $before;
  }

  /**
   * @return array<string, mixed> */
  public function get(string $entity, string $op, int|string|null $id): array {
    $key = $this->key($entity, $op, $id);
    return $this->store[$key] ?? [];
  }

  private function key(string $entity, string $op, int|string|null $id): string {
    return implode(':', [$entity, $op, (string) ($id ?? '')]);
  }

}
