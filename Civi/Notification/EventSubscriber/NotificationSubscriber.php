<?php

declare(strict_types = 1);

namespace Civi\Notification\EventSubscriber;

use Civi\API\Request;
use Civi\Core\Event\PostEvent;
use Civi\Core\Event\PreEvent;
use Civi\Notification\Handler\RuleSetHandler;
use Civi\Notification\Source\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class NotificationSubscriber implements EventSubscriberInterface {

  /**
   * Store the entity's old state in a cache.
   *
   * @var array<array-key, array<array-key, array<string, mixed>>>
   */
  private static array $entityCache = [];

  private EntityManager $entityManager;
  private RuleSetHandler $ruleSetHandler;

  public function __construct() {
    $this->entityManager = new EntityManager();
    $this->ruleSetHandler = new RuleSetHandler();
  }

  public static function getSubscribedEvents(): array {
    return [
      'hook_civicrm_pre' => 'onPre',
      'hook_civicrm_postCommit' => 'onPostCommit',
    ];
  }

  public function onPre(PreEvent $event): void {
    [, $entity, $id] = $event->getHookValues();

    if ($this->entityManager->hasActiveRuleSets($entity) && $id !== NULL) {
      // Capture old values before the change
      $oldValues = $this->loadEntityValues($entity, $id);

      // Store the entity's old state in a cache
      self::$entityCache[$entity][$id] = $oldValues;
    }
  }

  public function onPostCommit(PostEvent $event): void {
    [, $entity, $id] = $event->getHookValues();

    // Check if old values exist for this entity in the cache
    if (isset(self::$entityCache[$entity][$id])) {
      $oldValues = self::$entityCache[$entity][$id];
      // Retrieve new values after the change
      $newValues = $this->loadEntityValues($entity, $id);

      $ruleSets = $this->entityManager->loadRuleSetByEntityType($entity);

      foreach ($ruleSets as $ruleSet) {
        $this->ruleSetHandler->evaluateRuleSet($ruleSet, $newValues, $oldValues);
      }

      // Clean up cache after processing
      unset(self::$entityCache[$entity][$id]);
    }
  }

  /**
   * Load entity values from the database.
   *
   * @param string $entityType
   * @param int $entityID
   * @return array<string, mixed>
   */
  private function loadEntityValues(string $entityType, int $entityID): array {
    /** @var \Civi\Api4\Generic\AbstractAction $apiRequest */
    $apiRequest = Request::create($entityType, 'get', [
      'version' => 4,
      'where' => [['id', '=', $entityID]],
    ]);
    $result = $apiRequest->execute();

    return $result->first() ?? [];
  }

}
