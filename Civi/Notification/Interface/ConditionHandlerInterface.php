<?php

declare(strict_types = 1);

namespace Civi\Notification\Interface;

use Civi\Notification\Entity\ConditionEntity;

interface ConditionHandlerInterface {

  /**
   * @phpstan-param array<string, mixed> $oldValues
   */
  public function evaluateCondition(ConditionEntity $condition, array $oldValues): bool;

}
