<?php
declare(strict_types = 1);

namespace Civi\Notification;

final class ValueComparator {

  /**
   *
   * @param string $operator Operator ('=', '==', '!=', '<>', 'in').
   * @param mixed $left
   * @param mixed $right
   *
   * @return bool
   *
   * @throws \InvalidArgumentException If operator is not correct
   */
  public function compare(string $operator, $left, $right): bool {
    switch (strtolower($operator)) {
      case '=':
      case '==':
        return $left == $right;

      case '!=':
      case '<>':
        return $left != $right;

      case 'in':

        return \in_array($left, $right, TRUE);

      default:
        throw new \InvalidArgumentException("Unsupported operator '{$operator}'");
    }
  }

}
