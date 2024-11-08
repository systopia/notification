<?php

declare(strict_types = 1);

namespace Civi\Notification\Interface;

use Civi\Notification\Entity\FieldMonitoringEntity;

interface FieldMonitoringHandlerInterface {

  /**
   * @phpstan-param array<string, mixed> $newValues
   * @phpstan-param array<string, mixed> $oldValues
   * @return bool
   */
  public function evaluate(FieldMonitoringEntity $fieldMonitoring, array $newValues, array $oldValues): bool;

}
