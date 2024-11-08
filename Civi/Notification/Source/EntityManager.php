<?php

declare(strict_types = 1);

namespace Civi\Notification\Source;

use Civi\Api4\NotificationRuleSet;
use Civi\Notification\Entity\RuleSetEntity;

class EntityManager {

  public function loadRuleSetByEntityType(string $entityType): array {
    $notificationRuleSets = NotificationRuleSet::get(FALSE)
      ->addSelect('*')
      ->addWhere('monitored_entity_type', '=', $entityType)
      ->addWhere('is_active', '=', 1)
      ->execute();

    $ruleSets = [];

    foreach ($notificationRuleSets as $ruleSet) {
      $ruleSets[] = new RuleSetEntity($ruleSet);
    }

    return $ruleSets;
  }

  public function hasActiveRuleSets(string $entityType): bool {
    $result = NotificationRuleSet::get(FALSE)
      ->selectRowCount()
      ->addWhere('monitored_entity_type', '=', $entityType)
      ->addWhere('is_active', '=', TRUE)
      ->execute();

    return $result->count() > 0;
  }

}
