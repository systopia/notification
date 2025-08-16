<?php

declare(strict_types = 1);

namespace Civi\Notification\EventSubscriber;

use Civi\API\Request;
use Civi\Core\Event\PostEvent;
use Civi\Core\Event\PreEvent;
use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Handler\RuleSetHandler;
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

  /**
   * @var mixed */
  private $ruleSetHandler;
  /**
   * @var mixed */
  private $entityManager;

  /**
   * @param mixed|null $ruleSetHandler
   * @param mixed|null $entityManager
   */
  public function __construct($ruleSetHandler = NULL, $entityManager = NULL) {
    $this->ruleSetHandler = $ruleSetHandler;
    $this->entityManager  = $entityManager;

    try {
      $container = \Civi::container();
      if ($this->ruleSetHandler === NULL
        && \is_object($container)
        && \method_exists($container, 'has')
        && $container->has('notification.rule_set_handler')) {
        $this->ruleSetHandler = $container->get('notification.rule_set_handler');
      }
      if ($this->entityManager === NULL
        && \is_object($container)
        && \method_exists($container, 'has')
        && $container->has('notification.entity_manager')) {
        $this->entityManager = $container->get('notification.entity_manager');
      }
    }
    catch (\Throwable $e) {

    }
  }

  public static function getSubscribedEvents(): array {
    return [
      // Minimum priority because previous listeners could change the data.
      'hook_civicrm_pre' => ['onPre', PHP_INT_MIN],
      'hook_civicrm_postCommit' => 'onPostCommit',
    ];
  }

  public function onPre(PreEvent $event): void {
    if ($this->entityManager === NULL || $this->ruleSetHandler === NULL) {
      return;
    }
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
            'changeSet' => $changeSet,
          ];
        }
      }
    }
  }

  public function onPostCommit(PostEvent $event): void {
    if ($this->entityManager === NULL || $this->ruleSetHandler === NULL) {
      return;
    }
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
