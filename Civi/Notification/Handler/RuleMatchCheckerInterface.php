<?php
declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\RuleEntity;

interface RuleMatchCheckerInterface {

  public function isRuleMatched(RuleEntity $rule, NotificationContext $context): bool;

}
