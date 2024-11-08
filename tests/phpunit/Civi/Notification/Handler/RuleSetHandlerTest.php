<?php

namespace Civi\Notification\Handler;

use PHPUnit\Framework\TestCase;
use Civi\Notification\Entity\RuleSetEntity;
use Civi\Notification\Entity\RuleEntity;

/**
 * @covers \Civi\Notification\Handler\RuleSetHandler
 */
class RuleSetHandlerTest extends TestCase {

  private RuleSetHandler $ruleSetHandler;
  private $ruleHandlerMock;
  private $ruleSetEntityMock;
  private $ruleEntityMock;

  protected function setUp(): void {
    parent::setUp();

    $this->ruleHandlerMock = $this->createMock(RuleHandler::class);
    $this->ruleSetEntityMock = $this->createMock(RuleSetEntity::class);
    $this->ruleEntityMock = $this->createMock(RuleEntity::class);

    $this->ruleSetHandler = new RuleSetHandler();
    // Use reflection to set the private RuleHandler property
    $reflection = new \ReflectionClass($this->ruleSetHandler);
    $property = $reflection->getProperty('ruleHandler');
    $property->setAccessible(true);
    $property->setValue($this->ruleSetHandler, $this->ruleHandlerMock);
  }

  public function testEvaluateRuleSetExecutesRulesCorrectly(): void {
    $this->ruleSetEntityMock->method('getRules')->willReturn([$this->ruleEntityMock]);
    $this->ruleSetEntityMock->method('isExecuteOnlyFirstRule')->willReturn(false);
    $this->ruleHandlerMock->method('evaluateRule')->willReturn(true);
    $this->ruleEntityMock->method('isStopAfterThisRule')->willReturn(false);

    $newValues = ['field' => 'value_old'];
    $oldValues = ['field' => 'value_new'];

    $this->ruleSetHandler->evaluateRuleSet($this->ruleSetEntityMock, $newValues, $oldValues);

    $this->ruleHandlerMock->expects($this->once())
      ->method('evaluateRule')
      ->with($this->ruleEntityMock, $newValues, $oldValues);
  }

  public function testEvaluateRuleSetDoesNotEvaluateIfRuleIsNotMatched(): void {
    $this->ruleSetEntityMock->method('getRules')->willReturn([$this->ruleEntityMock]);
    $this->ruleHandlerMock->method('evaluateRule')->willReturn(false);

    $newValues = ['field' => 'value_old'];
    $oldValues = ['field' => 'value_new'];

    $this->ruleSetHandler->evaluateRuleSet($this->ruleSetEntityMock, $newValues, $oldValues);

    $this->ruleHandlerMock->expects($this->once())
      ->method('evaluateRule')
      ->with($this->ruleEntityMock, $newValues, $oldValues);
  }
}
