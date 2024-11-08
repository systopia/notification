<?php

declare(strict_types = 1);

namespace Civi\Notification\Interface;

use Civi\Notification\Entity\RuleSetEntity;

interface RuleSetHandlerInterface {

  /**
   * @phpstan-param array<string, mixed> $newValues
   * @phpstan-param array<string, mixed> $oldValues
   */
  public function evaluateRuleSet(RuleSetEntity $ruleSet, array $newValues, array $oldValues): void;

}
