<?php

declare(strict_types = 1);

namespace Civi\Notification\Interface;

use Civi\Notification\Entity\RuleEntity;

interface RuleHandlerInterface {

  /**
   * @phpstan-param array<string, mixed> $newValues
   * @phpstan-param array<string, mixed> $oldValues
   *
   * @return bool TRUE if rule has been executed, FALSE otherwise.
   */
  public function evaluateRule(RuleEntity $rule, array $newValues, array $oldValues): bool;

}
