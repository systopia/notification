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
   *   changeSet: array<string, array{0:mixed,1:mixed}>
   * }>>
   * The first keys are entity name and entity ID.
   */
  private array $entityCache = [];

  /**
   * @var mixed|null */
  private $ruleSetHandler;
  /**
   * @var mixed|null */
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
      // no-op
    }
  }

  public static function getSubscribedEvents(): array {
    if (!\Civi::settings()->get('notification_queue_job_enabled') ||
      \Civi::settings()->get('notification_processing_mode') != 'event') {
      return [];
    }
    return [
      'hook_civicrm_pre'        => 'onPre',
      'hook_civicrm_postCommit' => 'onPostCommit',
    ];
  }

  public function onPre(PreEvent $event): void {
    if ($this->entityManager === NULL || $this->ruleSetHandler === NULL) {
      return;
    }

    if ('edit' === $event->action && $event->id !== NULL) {
      $id = (int) $event->id;
      if ($id <= 0) {
        return;
      }

      if ($this->entityManager->hasActiveRuleSets($event->entity)) {

        $oldValues = $this->loadEntityValues($event->entity, $id);
        $newValues = $event->params + $oldValues;
        unset($newValues['custom']);

        $changed = array_diff_assoc($newValues, $oldValues);
        if ([] !== $changed) {
          $changeSet = [];
          foreach (array_keys($changed) as $fieldName) {
            $changeSet[$fieldName] = [$oldValues[$fieldName] ?? NULL, $newValues[$fieldName] ?? NULL];
          }

          $this->entityCache[$event->entity][$id] = [
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

    $id = (int) $event->id;
    if ($id <= 0) {
      return;
    }

    if (isset($this->entityCache[$event->entity][$id])) {
      [$oldValues, $newValues, $changeSet] = [
        $this->entityCache[$event->entity][$id]['oldValues'],
        $this->entityCache[$event->entity][$id]['newValues'],
        $this->entityCache[$event->entity][$id]['changeSet'],
      ];
      unset($this->entityCache[$event->entity][$id]);

      $ruleSets = $this->entityManager->loadRuleSetByEntityType($event->entity);
      foreach ($ruleSets as $ruleSet) {
        $this->ruleSetHandler->evaluateRuleSet(
          $ruleSet,
          new NotificationContext($oldValues, $newValues, $changeSet)
        );
      }
    }
  }

  /**
   * Load entity values from the database.
   *
   * @return array<string, mixed>
   * @throws \CRM_Core_Exception
   */
  private function loadEntityValues(string $entityType, int $entityId): array {
    /** @var \Civi\Api4\Generic\AbstractAction $apiRequest */
    $apiRequest = Request::create($entityType, 'get', [
      'version' => 4,
      'where' => [['id', '=', $entityId]],
      'select' => ['*'],
      'limit'  => 1,
    ]);

    $apiRequest->setCheckPermissions(FALSE);

    $result = $apiRequest->execute();
    return $result->single();
  }

}
