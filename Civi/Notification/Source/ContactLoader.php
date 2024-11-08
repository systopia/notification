<?php

declare(strict_types = 1);

namespace Civi\Notification\Source;

use Civi\Api4\Contact;
use Civi\Notification\Entity\ContactSelectionEntity;
use Civi\Notification\Entity\NotificationRecipient;
use Civi\Notification\Entity\RuleEntity;
use Civi\Notification\Interface\ContactLoaderInterface;

class ContactLoader implements ContactLoaderInterface {

  public function getContacts(ContactSelectionEntity $contactSelection): array {
    $orClause = [];
    if (count($contactSelection->getContactIds()) > 0) {
      $orClause[] = ['id', 'IN', $contactSelection->getContactIds()];
    }
    if (count($contactSelection->getGroups()) > 0) {
      $orClause[] = ['groups', 'IN', $contactSelection->getGroups()];
    }
    if (count($contactSelection->getContactTypeIds()) > 0) {
      $orClause[] = ['contact_type', 'IN', $contactSelection->getContactTypeIds()];
    }

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
      $recipients[] = new NotificationRecipient($contact);
    }

    return $recipients;
  }

  /**
   * @param \Civi\Notification\Entity\RuleEntity $rule
   * @return array<int, NotificationRecipient>
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function getContactsByRuleEmails(RuleEntity $rule): array {
    if ($rule->getPreferredLocationTypeId() !== NULL) {
      $whereClause = ['email.location_type_id', '=', $rule->getPreferredLocationTypeId()];
    }
    else {
      $whereClause = ['email.is_primary', '=', TRUE];
    }

    $contacts = Contact::get(FALSE)
      ->addSelect('id', 'display_name', 'email.email', 'preferred_language')
      ->addJoin('Email AS email', 'INNER', NULL, ['email.contact_id', '=', 'id'])
      ->addWhere('do_not_email', '=', FALSE)
      ->addWhere('email.email', 'IN', $rule->getEmailAddresses())
      ->addWhere(...$whereClause)
      ->execute()
      ->getArrayCopy();

    $recipients = [];
    foreach ($contacts as $contact) {
      $contact['email'] = $contact['email.email'];
      unset($contact['email.email']);
      $recipients[] = new NotificationRecipient($contact);
    }

    return $recipients;
  }

}
