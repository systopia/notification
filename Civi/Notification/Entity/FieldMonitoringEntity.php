<?php

declare(strict_types = 1);

namespace Civi\Notification\Entity;

/**
 * @phpstan-type fieldMonitoringEntityT array{
 *   id: int,
 *   field_name: string,
 *   operator_before: string,
 *   value_before: string,
 *   operator_after: string,
 *   value_after: string,
 * }
 *
 * @phpstan-extends AbstractEntity<fieldMonitoringEntityT>
 */
class FieldMonitoringEntity extends AbstractEntity {

  /**
   * @return string
   */
  public function getFieldName(): string {
    return $this->entityValues['field_name'];
  }

  /**
   * @return string
   */
  public function getOperatorBefore(): string {
    return $this->entityValues['operator_before'];
  }

  /**
   * @return string
   */
  public function getValueBefore(): string {
    return $this->entityValues['value_before'];
  }

  /**
   * @return string
   */
  public function getOperatorAfter(): string {
    return $this->entityValues['operator_after'];
  }

  /**
   * @return string
   */
  public function getValueAfter(): string {
    return $this->entityValues['value_after'];
  }

}
