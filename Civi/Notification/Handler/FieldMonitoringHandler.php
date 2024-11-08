<?php

declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Entity\FieldMonitoringEntity;
use Civi\Notification\Interface\FieldMonitoringHandlerInterface;
use Civi\Notification\Source\ValueComparator;

class FieldMonitoringHandler implements FieldMonitoringHandlerInterface {

  private ValueComparator $valueComparator;

  public function __construct() {
    $this->valueComparator = new ValueComparator();
  }

  public function evaluate(FieldMonitoringEntity $fieldMonitoring, array $newValues, array $oldValues): bool {
    $operatorBefore = $fieldMonitoring->getOperatorBefore();
    $operatorAfter = $fieldMonitoring->getValueAfter();
    $conditionBeforeValue = $fieldMonitoring->getValueBefore();
    $conditionAfterValue = $fieldMonitoring->getValueAfter();
    $fieldName = $fieldMonitoring->getFieldName();
    $newValue = $newValues[$fieldName] ?? NULL;
    $oldValue = $oldValues[$fieldName] ?? NULL;

    return $this->valueComparator->compareValues($conditionBeforeValue, $oldValue, $operatorBefore)
      && $this->valueComparator->compareValues($conditionAfterValue, $newValue, $operatorAfter);
  }

}
