<?php

declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\RuleSetEntity;

interface RuleSetHandlerInterface {

  public function evaluateRuleSet(RuleSetEntity $ruleSet, NotificationContext $context): void;

}
