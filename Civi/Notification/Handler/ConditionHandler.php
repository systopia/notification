<?php

declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Entity\ConditionEntity;
use Civi\Notification\Interface\ConditionHandlerInterface;
use Civi\Notification\Source\ValueComparator;

class ConditionHandler implements ConditionHandlerInterface {

  private ValueComparator $valueComparator;

  public function __construct() {
    $this->valueComparator = new ValueComparator();
  }

  public function evaluateCondition(ConditionEntity $condition, array $oldValues): bool {
    $operator = $condition->getOperator();
    $fieldName = $condition->getFieldName();
    $conditionValue = $condition->getValue();
    $oldValue = $oldValues[$fieldName] ?? NULL;

    return $this->valueComparator->compareValues($conditionValue, $oldValue, $operator);
  }

}
