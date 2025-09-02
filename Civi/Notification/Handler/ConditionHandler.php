<?php
declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\ConditionEntity;
use Civi\Notification\Util\ValueComparator;

final class ConditionHandler implements ConditionHandlerInterface {

  public function __construct(
    private ValueComparator $valueComparator
  ) {}

  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function evaluateCondition(ConditionEntity $condition, NotificationContext $context): bool {
  // phpcs:enable
    $field = $condition->getFieldName();
    $op    = strtolower((string) $condition->getOperator());
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
    return method_exists($this->valueComparator, 'equals')
      ? $this->valueComparator->equals($a, $b)
      : $a == $b;
  }

  private function in(mixed $needle, array $haystack): bool {
    return method_exists($this->valueComparator, 'in')
      ? $this->valueComparator->in($needle, $haystack)
      : in_array($needle, $haystack, FALSE);
  }

  private function gt(mixed $a, mixed $b): bool {
    return method_exists($this->valueComparator, 'greaterThan')
      ? $this->valueComparator->greaterThan($a, $b)
      : $a > $b;
  }

  private function gte(mixed $a, mixed $b): bool {
    return method_exists($this->valueComparator, 'greaterThanOrEqual')
      ? $this->valueComparator->greaterThanOrEqual($a, $b)
      : $a >= $b;
  }

  private function lt(mixed $a, mixed $b): bool {
    return method_exists($this->valueComparator, 'lessThan')
      ? $this->valueComparator->lessThan($a, $b)
      : $a < $b;
  }

  private function lte(mixed $a, mixed $b): bool {
    return method_exists($this->valueComparator, 'lessThanOrEqual')
      ? $this->valueComparator->lessThanOrEqual($a, $b)
      : $a <= $b;
  }

}
