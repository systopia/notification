<?php
declare(strict_types = 1);

namespace Civi\Notification\Util;

final class ValueComparator {

  public function compareValues(
    string $opBefore,
    mixed $expBefore,
    mixed $actBefore,
    string $opAfter,
    mixed $expAfter,
    mixed $actAfter
  ): bool {
    return $this->matches($opBefore, $expBefore, $actBefore)
      && $this->matches($opAfter, $expAfter, $actAfter);
  }

  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function matches(string $operator, mixed $expected, mixed $actual): bool {
    // phpcs:enable
    $op = strtolower(trim($operator));

    if (is_string($expected)) {
      $dec = json_decode($expected, TRUE);
      if (json_last_error() === JSON_ERROR_NONE) {
        $expected = $dec;
      }
    }

    $list  = is_array($expected) ? $expected : [$expected];
    $first = $list[0] ?? NULL;

    switch ($op) {
      case 'in':
        return in_array($actual, $list, TRUE);

      case 'not in':
      case 'not_in':
        return !in_array($actual, $list, TRUE);

      case '=':
      case '==':
      case 'eq':
        return $actual == $first;

      case '!=':
      case 'neq':
        return $actual != $first;

      default:
        return FALSE;
    }
  }

}
