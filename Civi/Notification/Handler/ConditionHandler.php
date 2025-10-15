<?php
declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\ConditionEntity;

final class ConditionHandler implements ConditionHandlerInterface {

  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function evaluateCondition(ConditionEntity $condition, NotificationContext $context): bool {
    // phpcs:enable
    $field = $condition->getFieldName();
    $op    = strtolower($condition->getOperator());
    $rhs   = $condition->getValue();

    $newValues = $context->getNewValues();
    $lhs = $newValues[$field] ?? NULL;

    switch ($op) {
      case 'eq':
      case '=':
        return $this->eq($lhs, $rhs);

      case 'neq':
      case '!=':
        return !$this->eq($lhs, $rhs);

      case 'in':
        return $this->in($lhs, (array) $rhs);

      case 'not_in':
      case 'not in':
      case 'nin':
        return !$this->in($lhs, (array) $rhs);

      case 'gt':
      case '>':
        return $this->gt($lhs, $rhs);

      case 'gte':
      case '>=':
        return $this->gte($lhs, $rhs);

      case 'lt':
      case '<':
        return $this->lt($lhs, $rhs);

      case 'lte':
      case '<=':
        return $this->lte($lhs, $rhs);
    }

    return FALSE;
  }

  public function evaluate(ConditionEntity $condition, NotificationContext $context): bool {
    return $this->evaluateCondition($condition, $context);
  }

  private function eq(mixed $a, mixed $b): bool {
    return $a == $b;
  }

  /**
   * @param array<int|string, mixed> $haystack
   */
  private function in(mixed $needle, array $haystack): bool {
    return in_array($needle, $haystack, TRUE);
  }

  private function gt(mixed $a, mixed $b): bool {
    return $a > $b;
  }

  private function gte(mixed $a, mixed $b): bool {
    return $a >= $b;
  }

  private function lt(mixed $a, mixed $b): bool {
    return $a < $b;
  }

  private function lte(mixed $a, mixed $b): bool {
    return $a <= $b;
  }

}
