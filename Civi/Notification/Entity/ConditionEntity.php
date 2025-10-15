<?php

declare(strict_types = 1);

namespace Civi\Notification\Entity;

/**
 * @phpstan-type conditionEntityT array{
 *   id: int,
 *   field_name: string,
 *   operator: string,
 *   value: string,
 * }
 *
 * @phpstan-extends AbstractEntity<conditionEntityT>
 */
class ConditionEntity extends AbstractEntity {

  public function getFieldName(): string {
    return $this->entityValues['field_name'];
  }

  public function getOperator(): string {
    return $this->entityValues['operator'];
  }

  public function getValue(): string {
    return $this->entityValues['value'];
  }

}
