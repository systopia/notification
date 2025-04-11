<?php

declare(strict_types = 1);

namespace Civi\Notification\EntityService;

use Civi\Api4\Contact;
use Civi\Notification\Data\NotificationRecipient;
use Civi\Notification\Entity\ContactSelectionEntity;
use Civi\Notification\Entity\RuleEntity;

final class ContactLoader implements ContactLoaderInterface {

  public function getContacts(ContactSelectionEntity $contactSelection, ?int $preferredLocationType): array {
    // @todo Allow implementations specific to the rule set and custom contact selection criteria.
    // @todo Throw exception if contact selection contains custom conditions and there's no specific implementation?

    $orClause = [];
    if ([] !== $contactSelection->getContactIds()) {
      $orClause[] = ['id', 'IN', $contactSelection->getContactIds()];
    }
    if ([] !== $contactSelection->getGroupIds()) {
      // @fixme
      $orClause[] = ['groups', 'IN', $contactSelection->getGroupIds()];
    }
    if ([] !== $contactSelection->getContactTypeIds()) {
      // @fixme
      $orClause[] = ['contact_type', 'IN', $contactSelection->getContactTypeIds()];
    }

    // @todo If preferred location type is given use it with primary address as fallback.

    $contacts = [];
    if (count($orClause) > 0) {
      $contacts = Contact::get(FALSE)
        ->addSelect('id', 'display_name', 'email.email', 'preferred_language')
        ->addJoin('Email AS email', 'INNER', NULL, ['email.contact_id', '=', 'id'])
        ->addClause('OR', ...$orClause)
        ->addWhere('do_not_email', '=', FALSE)
        ->execute()
        ->getArrayCopy();
    }

    $recipients = [];
    foreach ($contacts as $contact) {
      $contact['email'] = $contact['email.email'];
      unset($contact['email.email']);
      $recipients[] = new NotificationRecipient($contact['email'], $contact);
    }

    return $recipients;
  }

}
