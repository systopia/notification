<?php

declare(strict_types = 1);

namespace Civi\Notification\Entity;

/**
 * @phpstan-type fieldMonitoringEntityT array{
 *   id: int,
 *   field_name: string,
 *   operator_before: string,
 *   value_before: mixed,
 *   operator_after: string,
 *   value_after: mixed,
 * }
 *
 * @phpstan-extends AbstractEntity<fieldMonitoringEntityT>
 */
class FieldMonitoringEntity extends AbstractEntity {

  public function getFieldName(): string {
    return $this->entityValues['field_name'];
  }

  public function getOperatorBefore(): string {
    return $this->entityValues['operator_before'];
  }

  public function getValueBefore(): mixed {
    return $this->entityValues['value_before'];
  }

  public function getOperatorAfter(): string {
    return $this->entityValues['operator_after'];
  }

  public function getValueAfter(): mixed {
    return $this->entityValues['value_after'];
  }

}
