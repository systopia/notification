<?php
declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\RuleSetEntity;
use PHPUnit\Framework\TestCase;

final class RuleSetHandlerTest extends TestCase {

  /**
   *
   * @covers \Civi\Notification\Handler\RuleSetHandler::evaluateRuleSet
   */
  public function testEvaluateRuleSetDoesNotThrowWithEmptyRulesOrSkip(): void {

    $ruleHandler = $this->createMock(RuleHandlerInterface::class);
    $ruleHandler
      ->expects($this->never())
      ->method($this->anything());

    $sut = new RuleSetHandler($ruleHandler);

    $ruleSet = $this->getMockBuilder(RuleSetEntity::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getRules'])
      ->getMock();

    $ruleSet->method('getRules')->willReturn([]);

    $context = new NotificationContext([], [], []);

    $sut->evaluateRuleSet($ruleSet, $context);

    $this->addToAssertionCount(1);
  }

}
