<?php

declare(strict_types = 1);

namespace Civi\Notification\EntityService;

use Civi\Api4\NotificationRuleSet;
use Civi\Notification\Entity\RuleSetEntity;

class RuleSetManager {

  /**
   * @return list<RuleSetEntity>
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function loadRuleSetByEntityType(string $entityType): array {
    /** @var list<array{id:int, monitored_entity_type:string, source_entity_type:string, source_entity_id:int, is_active:bool, is_execute_only_first_rule:bool}> $notificationRuleSets */
    $notificationRuleSets = NotificationRuleSet::get(FALSE)
      ->addSelect('*')
      ->addWhere('monitored_entity_type', '=', $entityType)
      ->addWhere('is_active', '=', TRUE)
      ->execute()
      ->getArrayCopy();

    $ruleSets = [];

    foreach ($notificationRuleSets as $ruleSet) {
      /** @var array{id:int, monitored_entity_type:string, source_entity_type:string, source_entity_id:int, is_active:bool, is_execute_only_first_rule:bool} $ruleSet */
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
