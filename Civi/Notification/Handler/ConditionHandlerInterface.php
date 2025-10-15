<?php

declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\ConditionEntity;

interface ConditionHandlerInterface {

  public function evaluateCondition(ConditionEntity $condition, NotificationContext $context): bool;

}
