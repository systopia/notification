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
    $wantedTypes = array_map('strval', $selection->getContactTypeIds() ?? []);
    $contactIds  = [];

    // 1) IDs directos
    foreach ((array) ($selection->getContactIds() ?? []) as $cid) {
      if (is_numeric($cid)) {
        $contactIds[] = (int) $cid;
      }
    }

    $groupIds = (array) ($selection->getGroupIds() ?? []);
    if ($groupIds) {
      try {
        $rows = \Civi\Api4\GroupContact::get(FALSE)
          ->addWhere('group_id', 'IN', array_map('intval', $groupIds))
          ->addWhere('status', '=', 'Added')
          ->addSelect('contact_id')
          ->setLimit(0)
          ->execute();
        foreach ($rows as $row) {
          if (isset($row['contact_id']) && is_numeric($row['contact_id'])) {
            $contactIds[] = (int) $row['contact_id'];
          }
        }
      }
      catch (\Throwable $e) {
        DbLogger::log('warning', 'contactloader.groups', 'Failed to expand group_ids', [
          'group_ids' => $groupIds,
          'exception' => $e,
        ]);
      }
    }

    $contactIds = array_values(array_unique(array_filter($contactIds, fn($v) => $v > 0)));

    if (!$contactIds) {
      DbLogger::log('info', 'contactloader.empty', 'No contacts resolved from selection', [
        'selection' => [
          'contact_ids' => $selection->getContactIds(),
          'group_ids' => $groupIds,
          'contact_type_ids' => $wantedTypes,
        ],
      ]);
      return [];
    }

    $contactMeta = [];
    try {
      $q = \Civi\Api4\Contact::get(FALSE)
        ->addWhere('id', 'IN', $contactIds)
        ->addSelect('id', 'contact_type', 'display_name', 'preferred_language')
        ->setLimit(0);
      foreach ($q->execute() as $row) {
        $cid = (int) $row['id'];
        $contactMeta[$cid] = [
          'id' => $cid,
          'contact_type' => (string) ($row['contact_type'] ?? ''),
          'display_name' => (string) ($row['display_name'] ?? ''),
          'preferred_language' => $row['preferred_language'] ?? NULL,
        ];
      }
    }
    catch (\Throwable $e) {
      DbLogger::log('error', 'contactloader.meta', 'Failed to load contact meta', [
        'contact_ids' => $contactIds,
        'exception' => $e,
      ]);
    }

    if ($wantedTypes) {
      $contactIds = array_values(array_filter($contactIds, function (int $cid) use ($wantedTypes, $contactMeta): bool {
        $ct = $contactMeta[$cid]['contact_type'] ?? '';
        return $ct !== '' ? in_array($ct, $wantedTypes, TRUE) : TRUE;
      }));
    }

    $recipients = [];
    foreach ($contactIds as $cid) {
      try {
        $email = $this->loadPreferredEmail($cid, $preferredLocationTypeId);
        if (!$email) {
          DbLogger::log('warning', 'contactloader.noemail', 'No email for contact', ['contact_id' => $cid]);
          continue;
        }

        $meta = $contactMeta[$cid] ?? ['display_name' => '', 'preferred_language' => NULL];
        $display = (string) ($meta['display_name'] ?? '');
        $lang    = $meta['preferred_language'] ?? NULL;

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
      }
    }

    DbLogger::log('info', 'contactloader.result', 'Built recipients from selection', [
      'count' => count($recipients),
    ]);

    return $recipients;
  }

  private function loadPreferredEmail(int $contactId, ?int $preferredLocationTypeId): ?string {

    if ($preferredLocationTypeId) {
      try {
        $row = \Civi\Api4\Email::get(FALSE)
          ->addWhere('contact_id', '=', $contactId)
          ->addWhere('location_type_id', '=', $preferredLocationTypeId)
          ->addSelect('email')
          ->addOrderBy('is_primary', 'DESC')->addOrderBy('id', 'DESC')
          ->setLimit(1)
          ->execute()
          ->single();
        $em = $row['email'] ?? NULL;
        if (is_string($em) && $em !== '') {
          return $em;
        }
      }
      catch (\Throwable $e) {

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

      $em = $row['email'] ?? NULL;
      return is_string($em) && $em !== '' ? $em : NULL;
    }
    catch (\Throwable $e) {
      return NULL;
    }
  }

}
