<?php
declare(strict_types = 1);

namespace Civi\Notification\Data;

/**
 * @phpstan-type changeSetT array<string, array{mixed, mixed}>
 *   Mapping of field name to array containing old (index 0) and new value (index 1).
 */
final class NotificationContext {

  /**
   * @phpstan-var changeSetT
   */
  public readonly array $changeSet;

  /**
   * @phpstan-var array<string, mixed>
   */
  public readonly array $oldValues;

  /**
   * @phpstan-var array<string, mixed>
   */
  public readonly array $newValues;

  private array $extra = [];

  /**
   * @phpstan-param array<string, mixed> $oldValues
   * @phpstan-param array<string, mixed> $newValues
   * @phpstan-param changeSetT $changeSet
   */
  public function __construct(array $oldValues, array $newValues, array $changeSet) {
    $this->changeSet = $changeSet;
    $this->oldValues = $oldValues;
    $this->newValues = $newValues;
  }

  public function getExtra(string $key, mixed $default = NULL): mixed {
    return $this->extra[$key] ?? $default;
  }

  public function setExtra(string $key, mixed $value): static {
    $this->extra[$key] = $value;

    return $this;
  }

}
