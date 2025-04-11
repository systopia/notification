<?php
declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\RuleEntity;

final class RuleMatchChecker implements RuleMatchCheckerInterface {

  public function __construct(
    private ConditionHandlerInterface $conditionHandler,
    private FieldMonitoringHandlerInterface $fieldMonitorHandler
  ) {}

  public function isRuleMatched(RuleEntity $rule, NotificationContext $context): bool {
    foreach ($rule->getConditions() as $condition) {
      if (!$this->conditionHandler->evaluateCondition($condition, $context)) {
        return FALSE;
      }
    }

    foreach ($rule->getFieldMonitorings() as $fieldMonitoring) {
      if (!$this->fieldMonitorHandler->evaluate($fieldMonitoring, $context)) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
