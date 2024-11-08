<?php

declare(strict_types = 1);

namespace Civi\Notification\Interface;

use Civi\Notification\Entity\ContactSelectionEntity;
use Civi\Notification\Entity\NotificationRecipient;

interface ContactLoaderInterface {

  /**
   * @return array<int, NotificationRecipient>
   */
  public function getContacts(ContactSelectionEntity $contactSelection): array;

}
