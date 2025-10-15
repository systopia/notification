<?php
declare(strict_types = 1);

namespace Civi\Notification\Data;

/**
 * @phpstan-type changeSetT array<string, array{0:mixed,1:mixed}>
 */
final class NotificationContext {
  /**
   * @var array<string,mixed>
   */
  private array $oldValues;

  /**
   * @var array<string,mixed>
   */
  private array $newValues;

  /**
   * @var changeSetT
   */
  private array $changeSet;

  /**
   * @param array<string,mixed> $oldValues
   * @param array<string,mixed> $newValues
   * @param changeSetT $changeSet
   *
   * phpcs:disable Drupal.Commenting.FunctionComment.IncorrectTypeHint
   */
  public function __construct(array $oldValues, array $newValues, array $changeSet) {
    $this->oldValues = $oldValues;
    $this->newValues = $newValues;
    $this->changeSet = $changeSet;
  }

  /**
   * @return array<string,mixed>
   */
  public function getOldValues(): array {
    return $this->oldValues;
  }

  /**
   * @return array<string,mixed>
   */
  public function getNewValues(): array {
    return $this->newValues;
  }

  /**
   * @return changeSetT
   */
  public function getChangeSet(): array {
    return $this->changeSet;
  }

  /**
   * @return array<string,mixed>
   */
  public function getBeforeValues(): array {
    return $this->oldValues;
  }

  /**
   * @return array<string,mixed>
   */
  public function getAfterValues(): array {
    return $this->newValues;
  }

}
