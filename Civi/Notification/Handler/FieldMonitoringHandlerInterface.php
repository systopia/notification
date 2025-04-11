<?php

declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\FieldMonitoringEntity;

interface FieldMonitoringHandlerInterface {

  public function evaluate(FieldMonitoringEntity $fieldMonitoring, NotificationContext $context): bool;

}
