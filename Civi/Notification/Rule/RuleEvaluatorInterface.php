<?php
declare(strict_types = 1);

namespace Civi\Notification\Rule;

use Civi\Notification\DTO\NotificationEvent;

interface RuleEvaluatorInterface {

  public function match(NotificationEvent $event): array;

}
