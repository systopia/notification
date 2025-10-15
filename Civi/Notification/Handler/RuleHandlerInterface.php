<?php

declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\RuleEntity;

interface RuleHandlerInterface {

  /**
   * @return bool TRUE if rule has been executed, FALSE otherwise.
   */
  public function evaluateRule(RuleEntity $rule, NotificationContext $context): bool;

}
