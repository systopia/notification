<?php

declare(strict_types = 1);

namespace Civi\Notification\Entity;

/**
 * @phpstan-type contactSelectionEntityT array{
 *   id: int,
 *   contact_ids: list<int>,
 *   group_ids: list<int>,
 *   contact_type_ids: list<int>,
 *   custom: ?array,
 * }
 *
 * @phpstan-extends AbstractEntity<contactSelectionEntityT>
 */
class ContactSelectionEntity extends AbstractEntity {

  /**
   * @return list<int>
   */
  public function getContactIds(): array {
    return $this->entityValues['contact_ids'];
  }

  /**
   * @return list<int>
   */
  public function getGroupIds(): array {
    return $this->entityValues['group_ids'];
  }

  /**
   * @return list<int>
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
