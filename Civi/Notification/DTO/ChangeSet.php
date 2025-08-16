<?php
declare(strict_types = 1);

namespace Civi\Notification\DTO;

/**
 * @implements \IteratorAggregate<int, Change>
 */
class ChangeSet implements \IteratorAggregate, \Countable {
  /**
   * @var array<string, Change> */
  private array $changes = [];

  public function add(Change $change): void {
    $this->changes[$change->field] = $change;
  }

  public function get(string $field): ?Change {
    return $this->changes[$field] ?? NULL;
  }

  /**
   * @return array<int, Change>
   */
  public function all(): array {
    return array_values($this->changes);
  }

  /**
   * @return \Traversable<int, Change>
   */
  public function getIterator(): \Traversable {
    return new \ArrayIterator($this->all());
  }

  public function count(): int {
    return count($this->changes);
  }

}
