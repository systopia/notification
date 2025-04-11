<?php

declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\FieldMonitoringEntity;
use Civi\Notification\ValueComparator;

class FieldMonitoringHandler implements FieldMonitoringHandlerInterface {

  private ValueComparator $valueComparator;

  public function __construct(ValueComparator $valueComparator) {
    $this->valueComparator = $valueComparator;
  }

  public function evaluate(FieldMonitoringEntity $fieldMonitoring, NotificationContext $context): bool {
    $operatorBefore = $fieldMonitoring->getOperatorBefore();
    $operatorAfter = $fieldMonitoring->getOperatorAfter();
    $conditionBeforeValue = $fieldMonitoring->getValueBefore();
    $conditionAfterValue = $fieldMonitoring->getValueAfter();
    $fieldName = $fieldMonitoring->getFieldName();
    $newValue = $context->newValues[$fieldName] ?? NULL;
    $oldValue = $context->oldValues[$fieldName] ?? NULL;

    return $this->valueComparator->compareValues($oldValue, $operatorBefore, $conditionBeforeValue)
      && $this->valueComparator->compareValues($newValue, $operatorAfter, $conditionAfterValue);
  }

}
