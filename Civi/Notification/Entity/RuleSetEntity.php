<?php

declare(strict_types = 1);

namespace Civi\Notification\Entity;

use Civi\Api4\NotificationRule;

/**
 * @phpstan-type ruleSetEntityT array{
 *   id: int,
 *   monitored_entity_type: string,
 *   source_entity_type: string,
 *   source_entity_id: int,
 *   is_active: bool,
 *   is_execute_only_first_rule: bool,
 * }
 *
 * @phpstan-extends AbstractEntity<ruleSetEntityT>
 */
class RuleSetEntity extends AbstractEntity {

  /**
   * @return string
   */
  public function getMonitoredEntityType(): string {
    return $this->entityValues['monitored_entity_type'];
  }

  /**
   * @return string
   */
  public function getSourceEntityType(): string {
    return $this->entityValues['source_entity_type'];
  }

  /**
   * @return int
   */
  public function getSourceEntityId(): int {
    return $this->entityValues['source_entity_id'];
  }

  /**
   * @return bool
   */
  public function isActive(): bool {
    return $this->entityValues['is_active'];
  }

  /**
   * @return bool
   */
  public function isExecuteOnlyFirstRule(): bool {
    return $this->entityValues['is_execute_only_first_rule'];
  }

  /**
   * @return array<int, RuleEntity>
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getRules(): array {
    $notificationRules = NotificationRule::get(FALSE)
      ->addWhere('rule_set_id', '=', $this->getId())
      ->addWhere('is_active', '=', TRUE)
      ->addOrderBy('weight')
      ->execute();

    $rules = [];
    foreach ($notificationRules as $rule) {
      $rules[] = new RuleEntity($rule);
    }

    return $rules;
  }

}
