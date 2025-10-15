<?php

declare(strict_types = 1);

namespace Civi\Notification\EntityService;

use Civi\Notification\Entity\ContactSelectionEntity;

interface ContactLoaderInterface {

  /**
   * @return array<int, \Civi\Notification\Data\NotificationRecipient>
   */
  public function getContacts(ContactSelectionEntity $contactSelection, ?int $preferredLocationType): array;

}
