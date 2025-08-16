<?php
declare(strict_types = 1);

namespace Civi\Notification\Util;

class EntityRef {
  private const MAP = [
    'Activity' => 'civicrm_activity',
    'ActivityContact' => 'civicrm_activity_contact',
    'Case' => 'civicrm_case',
    'CaseContact' => 'civicrm_case_contact',
    'Contact' => 'civicrm_contact',
    'Email' => 'civicrm_email',
    'Phone' => 'civicrm_phone',
    'Address' => 'civicrm_address',
    'Relationship' => 'civicrm_relationship',
    'Group' => 'civicrm_group',
    'GroupContact' => 'civicrm_group_contact',
    'Note' => 'civicrm_note',
    'Participant' => 'civicrm_participant',
    'Event' => 'civicrm_event',
  ];

  public static function table(string $objectName): ?string {
    if (isset(self::MAP[$objectName])) {
      return self::MAP[$objectName];
    }
    if (str_starts_with($objectName, 'civicrm_')) {
      return $objectName;
    }
    return NULL;
  }

}
