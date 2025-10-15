<?php
declare(strict_types = 1);

namespace Civi\Notification\Hook;

use Civi\Notification\Event\EventFactoryInterface;
use Civi\Notification\Precheck\PreEnqueueMatcher;
use Civi\Notification\Queue\Enqueuer;
use Civi\Notification\Snapshot\SnapshotStore;
use Civi\Notification\Util\EntityRef;

class HookHandler {

  public function __construct(
    private SnapshotStore $snapshots,
    private EventFactoryInterface $factory,
    private Enqueuer $enqueuer,
    private PreEnqueueMatcher $preMatcher
  ) {}

  public function onPre(string $op, string $entity, int|string|null $id, array &$params): void {
    $table = EntityRef::table($entity);
    if ($table === NULL) {
      return;
    }
    $before = $this->resolveBefore($op, $entity, $id, $params);
    $this->snapshots->put($entity, $op, $id ?? 0, $before);
  }

  public function onPost(string $op, string $entity, int|string|null $id, mixed &$objectRef): void {
    // no-op
  }

  public function onPostCommit(string $op, string $entity, int|string|null $id, mixed &$objectRef): void {
    $table = EntityRef::table($entity);
    if ($table === NULL) {
      return;
    }

    $before = $this->snapshots->get($entity, $op, $id ?? 0);
    $after  = $this->resolveAfter($op, $entity, $id, $objectRef, $before);

    $context = [];

    if (!$this->preMatcher->shouldEnqueueFromHook($op, $entity, $id, $before, $after, $context)) {
      return;
    }

    $event = $this->factory->fromHook($op, $entity, $id, $before, $after, $context);
    if ($event) {
      $this->enqueuer->enqueueEvent($event);
    }
  }

  private function resolveBefore(string $op, string $entity, int|string|null $id, array $params): array {
    if ($op === 'create') {
      return [];
    }
    if ($id) {
      $table = EntityRef::table($entity);
      if ($table === NULL) {
        return [];
      }
      $dao = \CRM_Core_DAO::executeQuery(
        "SELECT * FROM {$table} WHERE id = %1",
        [1 => [$id, 'Integer']]
      );
      // @phpstan-ignore-next-line
      if ($dao->fetch()) {
        return get_object_vars($dao);
      }
    }
    return [];
  }

  /**
   * @param array<string, mixed> $before
   */
  private function resolveAfter(
    string $op,
    string $entity,
    int|string|null $id,
    mixed $objectRef,
    array $before
  ): array {
    if (is_array($objectRef) && $objectRef) {
      return $objectRef;
    }
    if ($id) {
      $table = EntityRef::table($entity);
      if ($table === NULL) {
        return [];
      }
      $dao = \CRM_Core_DAO::executeQuery(
        "SELECT * FROM {$table} WHERE id = %1",
        [1 => [$id, 'Integer']]
      );
      // @phpstan-ignore-next-line
      if ($dao->fetch()) {
        return get_object_vars($dao);
      }
    }
    return [];
  }

}
