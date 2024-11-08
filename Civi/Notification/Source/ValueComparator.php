<?php

declare(strict_types = 1);

namespace Civi\Notification\Source;

class ValueComparator {

  /**
   * Compare two values based on the given operator.
   *
   * @param mixed $value1
   * @param mixed $value2
   * @param string $operator
   * @return bool
   */
  public function compareValues($value1, $value2, string $operator): bool {
    switch ($operator) {
      case '=':
        return $value1 == $value2;

      case 'IN':
        return is_array($value1) && in_array($value2, $value1, TRUE);

      default:
        throw new \InvalidArgumentException("Unsupported operator: $operator");
    }
  }

}
