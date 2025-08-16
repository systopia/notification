<?php
declare(strict_types = 1);

namespace Civi\Notification\EventSubscriber;

use Civi\Core\Event\PostEvent;
use Civi\Core\Event\PreEvent;
use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\RuleSetEntity;
use Civi\Notification\Handler\RuleSetHandlerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * @covers \Civi\Notification\EventSubscriber\NotificationSubscriber::getSubscribedEvents
 * @covers \Civi\Notification\EventSubscriber\NotificationSubscriber::onPre
 * @covers \Civi\Notification\EventSubscriber\NotificationSubscriber::onPostCommit
 */
final class NotificationSubscriberTest extends TestCase {

  /**
   * @var \PHPUnit\Framework\MockObject\MockObject&RuleSetHandlerInterface */
  private $ruleSetHandlerMock;

  /**
   * @var \PHPUnit\Framework\MockObject\MockObject */
  private $entityManagerMock;

  protected function setUp(): void {
    parent::setUp();

    $this->ruleSetHandlerMock = $this->getMockBuilder(RuleSetHandlerInterface::class)->getMock();

    $this->entityManagerMock = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['hasActiveRuleSets', 'loadRuleSetByEntityType'])
      ->getMock();
  }

  public function testGetSubscribedEvents(): void {
    $events = NotificationSubscriber::getSubscribedEvents();

    static::assertArrayHasKey('hook_civicrm_pre', $events);
    static::assertArrayHasKey('hook_civicrm_postCommit', $events);

    $preListener = $events['hook_civicrm_pre'];
    $postListener = $events['hook_civicrm_postCommit'];

    $preMethod = \is_string($preListener) ? $preListener
      : (\is_array($preListener) ? ($preListener[0] ?? NULL) : NULL);
    $postMethod = \is_string($postListener) ? $postListener
      : (\is_array($postListener) ? ($postListener[0] ?? NULL) : NULL);

    static::assertSame('onPre', $preMethod);
    static::assertSame('onPostCommit', $postMethod);
    static::assertTrue(\method_exists(NotificationSubscriber::class, 'onPre'));
    static::assertTrue(\method_exists(NotificationSubscriber::class, 'onPostCommit'));
  }

  /**
   *
   * @covers \Civi\Notification\EventSubscriber\NotificationSubscriber::onPre
   */
  public function testOnPreDoesNothingIfNoActiveRuleSets(): void {
    $this->entityManagerMock->method('hasActiveRuleSets')->willReturn(FALSE);

    $subscriber = new NotificationSubscriber($this->ruleSetHandlerMock, $this->entityManagerMock);

    $preEvent = (new ReflectionClass(PreEvent::class))->newInstanceWithoutConstructor();
    $preEvent->action = 'edit';
    $preEvent->entity = 'Contact';
    $preEvent->id = 123;
    $preEvent->params = ['first_name' => 'Bob'];

    $subscriber->onPre($preEvent);

    $rp = new ReflectionProperty(NotificationSubscriber::class, 'entityCache');
    $rp->setAccessible(TRUE);
    $cache = $rp->isStatic() ? $rp->getValue() : $rp->getValue($subscriber);

    static::assertIsArray($cache);
    static::assertTrue(empty($cache) || !isset($cache['Contact'][123]));
  }

  /**
   *
   * @covers \Civi\Notification\EventSubscriber\NotificationSubscriber::onPostCommit
   */
  public function testOnPostCommitEvaluatesRuleSets(): void {
    $ruleSet1 = $this->createMock(RuleSetEntity::class);
    $ruleSet2 = $this->createMock(RuleSetEntity::class);

    $this->entityManagerMock->method('loadRuleSetByEntityType')->willReturn([$ruleSet1, $ruleSet2]);

    $this->ruleSetHandlerMock->expects($this->exactly(2))
      ->method('evaluateRuleSet')
      ->with(
        $this->isInstanceOf(RuleSetEntity::class),
        $this->isInstanceOf(NotificationContext::class)
      );

    $subscriber = new NotificationSubscriber($this->ruleSetHandlerMock, $this->entityManagerMock);

    $record = [
      'oldValues' => ['first_name' => 'Alice'],
      'newValues' => ['first_name' => 'Bob'],
      'changeSet' => ['first_name' => ['Alice', 'Bob']],
    ];

    $rp = new ReflectionProperty(NotificationSubscriber::class, 'entityCache');
    $rp->setAccessible(TRUE);
    if ($rp->isStatic()) {
      $rp->setValue(['Contact' => [123 => $record]]);
    }
    else {
      $rp->setValue($subscriber, ['Contact' => [123 => $record]]);
    }

    $postEvent = (new ReflectionClass(PostEvent::class))->newInstanceWithoutConstructor();
    $postEvent->entity = 'Contact';
    $postEvent->id = 123;

    $subscriber->onPostCommit($postEvent);

    $cache = $rp->isStatic() ? $rp->getValue() : $rp->getValue($subscriber);
    static::assertTrue(!isset($cache['Contact'][123]), 'Cache debería limpiarse tras onPostCommit');

    // Limpieza
    if ($rp->isStatic()) {
      $rp->setValue([]);
    }
    else {
      $rp->setValue($subscriber, []);
    }
  }

}
