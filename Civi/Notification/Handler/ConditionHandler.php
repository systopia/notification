<?php

declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\ConditionEntity;
use Civi\Notification\ValueComparator;

class ConditionHandler implements ConditionHandlerInterface {

  private ValueComparator $valueComparator;

  public function __construct(ValueComparator $valueComparator) {
    $this->valueComparator = $valueComparator;
  }

  public function evaluateCondition(ConditionEntity $condition, NotificationContext $context): bool {
    $operator = $condition->getOperator();
    $fieldName = $condition->getFieldName();
    $conditionValue = $condition->getValue();
    $value = $context->newValues[$fieldName] ?? NULL;

    return $this->valueComparator->compareValues($value, $operator, $conditionValue);
  }

}
