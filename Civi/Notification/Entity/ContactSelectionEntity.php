<?php

declare(strict_types = 1);

namespace Civi\Notification\Entity;

/**
 * @phpstan-type contactSelectionEntityT array{
 *   id: int,
 *   contact_ids: array<string, int>,
 *   groups: array<string, int>,
 *   contact_type_ids: array<string, string>,
 *   custom: ?array,
 * }
 *
 * @phpstan-extends AbstractEntity<contactSelectionEntityT>
 */
class ContactSelectionEntity extends AbstractEntity {

  /**
   * @return array<string, int>
   */
  public function getContactIds(): array {
    return $this->entityValues['contact_ids'];
  }

  /**
   * @return array<string, int>
   */
  public function getGroups(): array {
    return $this->entityValues['groups'];
  }

  /**
   * @return array<string, string>
   */
  public function getContactTypeIds(): array {
    return $this->entityValues['contact_type_ids'];
  }

  /**
   * @return array<string, mixed>|null
   */
  public function getCustom(): ?array {
    return $this->entityValues['custom'];
  }

}
