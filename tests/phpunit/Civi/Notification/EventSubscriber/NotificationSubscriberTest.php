<?php

namespace Civi\Notification\EventSubscriber;

use Civi\Core\Event\PostEvent;
use Civi\Core\Event\PreEvent;
use Civi\Notification\Entity\RuleSetEntity;
use Civi\Notification\Handler\RuleSetHandler;
use Civi\Notification\Source\EntityManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Civi\Notification\EventSubscriber\NotificationSubscriber
 */
class NotificationSubscriberTest extends TestCase {


  private $preEventMock;
  private $postEventMock;
  private $entityManagerMock;
  private $ruleSetHandlerMock;
  private $notificationSubscriber;

  protected function setUp(): void {
    $this->preEventMock = $this->createMock(PreEvent::class);
    $this->postEventMock = $this->createMock(PostEvent::class);
    $this->entityManagerMock = $this->createMock(EntityManager::class);
    $this->ruleSetHandlerMock = $this->createMock(RuleSetHandler::class);
    $this->ruleSetEntityMock = $this->createMock(RuleSetEntity::class);

    $this->notificationSubscriber = new NotificationSubscriber();

    $reflection = new \ReflectionClass($this->notificationSubscriber);
    $propertyEntityManager = $reflection->getProperty('entityManager');
    $propertyEntityManager->setAccessible(true);
    $propertyEntityManager->setValue($this->notificationSubscriber, $this->entityManagerMock);

    $propertyRuleSetHandler = $reflection->getProperty('ruleSetHandler');
    $propertyRuleSetHandler->setAccessible(true);
    $propertyRuleSetHandler->setValue($this->notificationSubscriber, $this->ruleSetHandlerMock);
  }

  public function testGetSubscribedEvents(): void {
    $events = NotificationSubscriber::getSubscribedEvents();
    $this->assertArrayHasKey('hook_civicrm_pre', $events);
    $this->assertArrayHasKey('hook_civicrm_postCommit', $events);

    foreach ($events as $method) {
      static::assertTrue(method_exists(NotificationSubscriber::class, $method));
    }
  }

  public function testOnPreEventCachesEntityState(): void {
    $this->preEventMock->method('getHookValues')->willReturn([null, 'Entity', 123]);
    $this->entityManagerMock->method('hasActiveRuleSets')->willReturn(true);

    //$this->setEntityCacheValue(['Entity' => [123 => ['field' => 'value_old']]]);

    $this->notificationSubscriber->onPre($this->preEventMock);

    $cachedData = $this->getEntityCache();
    $this->assertArrayHasKey('Entity', $cachedData);
    $this->assertArrayHasKey(123, $cachedData['Entity']);
  }

  public function testOnPostCommitEvaluatesRuleSets(): void {
    $oldValues = ['field' => 'value_old'];
    $newValues = ['field' => 'value_new'];

    $this->postEventMock->method('getHookValues')->willReturn([null, 'Entity', 123]);

    $this->setEntityCacheValue(['Entity' => [123 => $oldValues]]);

    $this->entityManagerMock->method('loadRuleSetByEntityType')->willReturn([$this->ruleSetEntityMock]);

    $this->notificationSubscriber->onPostCommit($this->postEventMock);

    $this->ruleSetHandlerMock->expects($this->once())
      ->method('evaluateRuleSet')
      ->with($this->ruleSetEntityMock, $newValues, $oldValues);

//    $entityCache = $this->getEntityCache();
//    $this->assertArrayHasKey('Entity', $entityCache);
//    $this->notificationSubscriber->onPostCommit($this->postEventMock);
  }

  private function setEntityCacheValue(array $value): void {
    $reflection = new \ReflectionClass(NotificationSubscriber::class);
    $property = $reflection->getProperty('entityCache');
    $property->setAccessible(true);

    $property->setValue($value);
  }

  private function getEntityCache(): array {
    $reflection = new \ReflectionClass(NotificationSubscriber::class);
    $property = $reflection->getProperty('entityCache');
    $property->setAccessible(true);

    return $property->getValue();
  }

}
