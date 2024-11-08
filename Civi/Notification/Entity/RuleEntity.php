<?php

declare(strict_types = 1);

namespace Civi\Notification\Entity;

use Civi\Api4\NotificationCondition;
use Civi\Api4\NotificationContactSelection;
use Civi\Api4\NotificationFieldMonitoring;
use Civi\Api4\NotificationRuleMessageTemplate;

/**
 * @phpstan-type ruleEntityT array{
 *   id: int,
 *   preferred_location_type_id: int|null,
 *   email_addresses: array<string, string>|null,
 *   is_active: bool,
 *   is_respect_communication_suspension: bool,
 *   is_stop_after_this_rule: bool,
 *   weight: int|null,
 * }
 *
 * @phpstan-extends AbstractEntity<ruleEntityT>
 */
class RuleEntity extends AbstractEntity {

  /**
   * @return int|null
   */
  public function getPreferredLocationTypeId(): ?int {
    return $this->entityValues['preferred_location_type_id'];
  }

  /**
   * @return array<string, string>|null
   */
  public function getEmailAddresses(): ?array {
    return $this->entityValues['email_addresses'];
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
  public function isRespectCommunicationSuspension(): bool {
    return $this->entityValues['is_respect_communication_suspension'];
  }

  /**
   * @return bool
   */
  public function isStopAfterThisRule(): bool {
    return $this->entityValues['is_stop_after_this_rule'];
  }

  /**
   * @return int|null
   */
  public function getWeight(): ?int {
    return $this->entityValues['weight'];
  }

  /**
   * @return array<int, ConditionEntity>
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getConditions(): array {
    $notificationConditions = NotificationCondition::get(FALSE)
      ->addWhere('rule_id', '=', $this->getId())
      ->execute();

    $conditions = [];
    foreach ($notificationConditions as $condition) {
      $conditions[] = new ConditionEntity($condition);
    }

    return $conditions;
  }

  /**
   * @return array<int, FieldMonitoringEntity>
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getFieldMonitorings(): array {
    $notificationFieldMonitorings = NotificationFieldMonitoring::get(FALSE)
      ->addWhere('rule_id', '=', $this->getId())
      ->execute();

    $fieldMonitorings = [];
    foreach ($notificationFieldMonitorings as $fieldMonitoring) {
      $fieldMonitorings[] = new FieldMonitoringEntity($fieldMonitoring);
    }

    return $fieldMonitorings;
  }

  /**
   * @return array<int, ContactSelectionEntity>
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getContactSelections(): array {
    $notificationContactSelections = NotificationContactSelection::get(FALSE)
      ->addWhere('rule_id', '=', $this->getId())
      ->execute();

    $contactSelections = [];
    foreach ($notificationContactSelections as $contactSelection) {
      $contactSelections[] = new ContactSelectionEntity($contactSelection);
    }

    return $contactSelections;
  }

  /**
   * @return array<int, RuleMsgTemplateEntity>
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getMsgTemplates(): array {
    $notificationRuleMsgTemplates = NotificationRuleMessageTemplate::get(FALSE)
      ->addWhere('rule_id', '=', $this->getId())
      ->execute();

    $msgTemplates = [];
    foreach ($notificationRuleMsgTemplates as $msgTemplate) {
      $msgTemplates[] = new RuleMsgTemplateEntity($msgTemplate);
    }

    return $msgTemplates;
  }

}
