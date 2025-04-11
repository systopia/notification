<?php
declare(strict_types = 1);

namespace Civi\Notification;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\RuleEntity;

interface TokenContextGeneratorInterface {

  /**
   * @phpstan-return array<string, mixed>
   */
  public function generateTokenContext(RuleEntity $rule, NotificationContext $context): array;

}
