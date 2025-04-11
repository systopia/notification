<?php

declare(strict_types = 1);

namespace Civi\Notification\EventSubscriber;

use Civi\API\Request;
use Civi\Core\Event\PostEvent;
use Civi\Core\Event\PreEvent;
use Civi\Notification\Data\NotificationContext;
use Civi\Notification\EntityService\RuleSetManager;
use Civi\Notification\Handler\RuleSetHandler;
use Civi\Notification\Handler\RuleSetHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @phpstan-import-type changeSetT from NotificationContext
 */
final class NotificationSubscriber implements EventSubscriberInterface {

  /**
   * @phpstan-var array<string, array<int, array{
   *   oldValues: array<string, mixed>,
   *   newValues: array<string, mixed>,
   *   changeSet: changeSetT
   * }>>
   * The first keys are entity name and entity ID.
   */
  private array $entityCache = [];

  public function __construct(
    private RuleSetManager $entityManager,
    private RuleSetHandlerInterface $ruleSetHandler
  ) {}

  public static function getSubscribedEvents(): array {
    return [
      // Minimum priority because previous listeners could change the data.
      'hook_civicrm_pre' => ['onPre', PHP_INT_MIN],
      'hook_civicrm_postCommit' => 'onPostCommit',
    ];
  }

  public function onPre(PreEvent $event): void {
    // @todo What about 'create' and 'delete'?
    if ('edit' === $event->action && $event->id !== NULL) {
      if ($this->entityManager->hasActiveRuleSets($event->entity)) {
        // Capture old values before the change
        // @todo Also load custom values. (At least those in $event->params.)
        $oldValues = $this->loadEntityValues($event->entity, $event->id);
        $newValues = $event->params + $oldValues;
        unset($newValues['custom']);

        $changed = array_diff_assoc($newValues, $oldValues);
        if ([] !== $changed) {
          $changeSet = [];
          foreach (array_keys($changed) as $fieldName) {
            $changeSet[$fieldName] = [$oldValues[$fieldName], $newValues[$fieldName]];
          }

          $this->entityCache[$event->entity][$event->id] = [
            'oldValues' => $oldValues,
            'newValues' => $newValues,
            'changeSet' => $changeSet
          ];
        }
      }
    }
  }

  public function onPostCommit(PostEvent $event): void {
    // Check if old values exist for this entity in the cache.
    if (isset($this->entityCache[$event->entity][$event->id])) {
      [$oldValues, $newValues, $changeSet] = [
        $this->entityCache[$event->entity][$event->id]['oldValues'],
        $this->entityCache[$event->entity][$event->id]['newValues'],
        $this->entityCache[$event->entity][$event->id]['changeSet'],
      ];
      unset($this->entityCache[$event->entity][$event->id]);

      $ruleSets = $this->entityManager->loadRuleSetByEntityType($event->entity);
      foreach ($ruleSets as $ruleSet) {
        $this->ruleSetHandler->evaluateRuleSet($ruleSet, new NotificationContext($oldValues, $newValues, $changeSet));
      }
    }
  }

  /**
   * Load entity values from the database.
   *
   * @return array<string, mixed>
   *
   * @throws \CRM_Core_Exception
   */
  private function loadEntityValues(string $entityType, int $entityId): array {
    /** @var \Civi\Api4\Generic\AbstractAction $apiRequest */
    $apiRequest = Request::create($entityType, 'get', [
      'version' => 4,
      'where' => [['id', '=', $entityId]],
    ]);
    $result = $apiRequest->execute();

    return $result->single();
  }

}
