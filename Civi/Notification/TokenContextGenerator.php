<?php
declare(strict_types = 1);

namespace Civi\Notification;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\RuleEntity;

final class TokenContextGenerator implements TokenContextGeneratorInterface {

  public function generateTokenContext(RuleEntity $rule, NotificationContext $context): array {
    // @todo It should be possible to use implementations specific to the rule set.

    /**
     * @todo Return an array mapping `<monitored entity type>Id` to the entity
     * id and `<source entity type>Id` to `<source entity ID>`. The entity types
     * start with a lower case character.
     */
    return [];
  }

}
