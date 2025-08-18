<?php

declare(strict_types = 1);

namespace Civi\Notification\EntityService;

use Civi\Api4\Contact;
use Civi\Notification\Data\NotificationRecipient;
use Civi\Notification\Entity\ContactSelectionEntity;

final class ContactLoader implements ContactLoaderInterface {

  /**
   * @return list<NotificationRecipient>
   */
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

    /** @var list<array{id:int, display_name:string, email:string, preferred_language:string|null}> $contacts */
    $contacts = [];
    if (count($orClause) > 0) {
      $contacts = Contact::get(FALSE)
        ->addSelect('id', 'display_name', 'email.email AS email', 'preferred_language')
        ->addJoin('Email AS email', 'INNER', NULL, ['email.contact_id', '=', 'id'])
        ->addClause('OR', ...$orClause)
        ->addWhere('do_not_email', '=', FALSE)
        ->execute()
        ->getArrayCopy();
    }

    /** @var list<NotificationRecipient> $recipients */
    $recipients = [];
    foreach ($contacts as $contact) {
      /** @var array{id:int, display_name:string, email:string, preferred_language:string|null} $contact */
      $email = (string) $contact['email'];
      $recipientContact = [
        'id' => (int) $contact['id'],
        'display_name' => (string) $contact['display_name'],
        'email' => $email,
        'preferred_language' => $contact['preferred_language'] !== '' ? (string) $contact['preferred_language'] : NULL,
      ];
      $recipients[] = new NotificationRecipient($email, $recipientContact);
    }

    return $recipients;
  }

}
