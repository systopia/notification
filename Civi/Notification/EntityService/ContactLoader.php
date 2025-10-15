<?php
declare(strict_types = 1);

namespace Civi\Notification\EntityService;

use Civi\Notification\Data\NotificationRecipient;
use Civi\Notification\Entity\ContactSelectionEntity;
use Civi\Notification\Support\DbLogger;

final class ContactLoader implements ContactLoaderInterface {

  /**
   * @return list<NotificationRecipient>
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded
  public function getContacts(ContactSelectionEntity $selection, ?int $preferredLocationTypeId = NULL): array {
    // phpcs:enable

    /** @var array<int,string> $wantedTypes */
    $wantedTypes = array_map('strval', $selection->getContactTypeIds());
    /** @var array<int,int> $contactIds */
    $contactIds = [];

    // 1) IDs directos
    foreach ((array) $selection->getContactIds() as $cid) {
      if (is_numeric($cid)) {
        $contactIds[] = (int) $cid;
      }
    }

    /** @var array<int,int|string> $groupIds */
    $groupIds = (array) $selection->getGroupIds();
    if (count($groupIds) > 0) {
      try {
        $rows = \Civi\Api4\GroupContact::get(FALSE)
          ->addWhere('group_id', 'IN', array_map('intval', $groupIds))
          ->addWhere('status', '=', 'Added')
          ->addSelect('contact_id')
          ->setLimit(0)
          ->execute();
        foreach ($rows as $row) {
          $rowArr = (array) $row;
          if (isset($rowArr['contact_id']) && is_numeric($rowArr['contact_id'])) {
            $contactIds[] = (int) $rowArr['contact_id'];
          }
        }
      }
      catch (\Throwable $e) {
        DbLogger::log('warning', 'contactloader.groups', 'Failed to expand group_ids', [
          'group_ids' => $groupIds,
          'exception' => $e,
        ]);
        throw $e;
      }
    }

    /** @var array<int,int> $contactIds */
    $contactIds = array_values(
      array_unique(
        array_filter($contactIds, static fn (int $v): bool => $v > 0)
      )
    );

    if (count($contactIds) === 0) {
      DbLogger::log('info', 'contactloader.empty', 'No contacts resolved from selection', [
        'selection' => [
          'contact_ids'      => $selection->getContactIds(),
          'group_ids'        => $groupIds,
          'contact_type_ids' => $wantedTypes,
        ],
      ]);
      return [];
    }

    /** @var array<int, array{id:int,contact_type:string,display_name:string,preferred_language:string|null}> $contactMeta */
    $contactMeta = [];
    try {
      $q = \Civi\Api4\Contact::get(FALSE)
        ->addWhere('id', 'IN', $contactIds)
        ->addSelect('id', 'contact_type', 'display_name', 'preferred_language')
        ->setLimit(0);
      foreach ($q->execute() as $row) {
        $r = (array) $row;
        $cid = $this->toInt($r['id'] ?? NULL);
        if ($cid <= 0) {
          continue;
        }
        $contactMeta[$cid] = [
          'id' => $cid,
          'contact_type' => isset($r['contact_type']) && is_string($r['contact_type']) ? $r['contact_type'] : '',
          'display_name' => isset($r['display_name']) && is_string($r['display_name']) ? $r['display_name'] : '',
          'preferred_language' => (isset($r['preferred_language']) && is_string($r['preferred_language']))
          ? $r['preferred_language'] : NULL,
        ];
      }
    }
    catch (\Throwable $e) {
      DbLogger::log('error', 'contactloader.meta', 'Failed to load contact meta', [
        'contact_ids' => $contactIds,
        'exception' => $e,
      ]);
      throw $e;
    }

    if (count($wantedTypes) > 0) {
      $contactIds = array_values(array_filter(
        $contactIds,
        static function (int $cid) use ($wantedTypes, $contactMeta): bool {
          $ct = $contactMeta[$cid]['contact_type'] ?? '';
          return $ct !== '' ? in_array($ct, $wantedTypes, TRUE) : TRUE;
        }
      ));
    }

    /** @var list<NotificationRecipient> $recipients */
    $recipients = [];
    foreach ($contactIds as $cid) {
      try {
        $email = $this->loadPreferredEmail($cid, $preferredLocationTypeId);
        if ($email === NULL || $email === '') {
          DbLogger::log('warning', 'contactloader.noemail', 'No email for contact', ['contact_id' => $cid]);
          continue;
        }

        $meta = $contactMeta[$cid] ?? ['display_name' => '', 'preferred_language' => NULL];
        $display = isset($meta['display_name']) && is_string($meta['display_name']) ? $meta['display_name'] : '';
        $lang    = $meta['preferred_language'] ?? NULL;
        $lang    = is_string($lang) ? $lang : NULL;

        $contactData = [
          'id' => $cid,
          'display_name' => $display,
          'email' => $email,
          'preferred_language' => $lang,
        ];

        $recipients[] = new NotificationRecipient($email, $contactData);
      }
      catch (\Throwable $e) {
        DbLogger::log('warning', 'contactloader.contact', 'Failed to build recipient for contact', [
          'contact_id' => $cid,
          'exception' => $e,
        ]);
        throw $e;
      }
    }

    DbLogger::log('info', 'contactloader.result', 'Built recipients from selection', [
      'count' => count($recipients),
    ]);

    return $recipients;
  }

  private function loadPreferredEmail(int $contactId, ?int $preferredLocationTypeId): ?string {

    if ($preferredLocationTypeId !== NULL && $preferredLocationTypeId > 0) {
      try {
        $row = \Civi\Api4\Email::get(FALSE)
          ->addWhere('contact_id', '=', $contactId)
          ->addWhere('location_type_id', '=', $preferredLocationTypeId)
          ->addSelect('email')
          ->addOrderBy('is_primary', 'DESC')->addOrderBy('id', 'DESC')
          ->setLimit(1)
          ->execute()
          ->single();
        $em = is_array($row) ? ($row['email'] ?? NULL) : NULL;
        if (is_string($em) && $em !== '') {
          return $em;
        }
      }
      catch (\Throwable $e) {
        DbLogger::log('warning', 'contactloader.emailPreferred', 'Failed to load preferred email', [
          'contact_id' => $contactId,
          'preferred_location_type_id' => $preferredLocationTypeId,
          'exception' => $e,
        ]);
        throw $e;
      }
    }

    // 4.b) Fallback: primary primero
    try {
      $row = \Civi\Api4\Email::get(FALSE)
        ->addWhere('contact_id', '=', $contactId)
        ->addSelect('email')
        ->addOrderBy('is_primary', 'DESC')->addOrderBy('id', 'DESC')
        ->setLimit(1)
        ->execute()
        ->single();

      $em = is_array($row) ? ($row['email'] ?? NULL) : NULL;
      return (is_string($em) && $em !== '') ? $em : NULL;
    }
    catch (\Throwable $e) {
      DbLogger::log('error', 'contactloader.emailFallback', 'Failed to load fallback email', [
        'contact_id' => $contactId,
        'exception' => $e,
      ]);
      throw $e;
    }
  }

  /**
   * Safe int conversion (int, float, numeric-string → int; else 0) */
  private function toInt(mixed $value): int {
    if (is_int($value)) {
      return $value;
    }
    if (is_float($value)) {
      return (int) $value;
    }
    if (is_string($value)) {
      $v = trim($value);
      if ($v !== '' && preg_match('/^-?\d+$/', $v) === 1) {
        return (int) $v;
      }
    }
    return 0;
  }

}
