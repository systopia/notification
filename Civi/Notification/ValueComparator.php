<?php

declare(strict_types = 1);

namespace Civi\Notification;

use Webmozart\Assert\Assert;

class ValueComparator {

  /**
   * Compare two values based on the given operator.
   */
  public function compareValues(mixed $value1, string $operator, mixed $value2): bool {
    switch ($operator) {
      case '=':
        return $value1 == $value2;

      case 'IN':
        assert(is_array($value2));
        return in_array($value1, $value2, TRUE);

      default:
        throw new \InvalidArgumentException("Unsupported operator: $operator");
    }
  }

}
