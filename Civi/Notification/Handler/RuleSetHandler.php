<?php

declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\RuleSetEntity;

class RuleSetHandler implements RuleSetHandlerInterface {

  private RuleHandlerInterface $ruleHandler;

  public function __construct(RuleHandlerInterface $ruleHandler) {
    $this->ruleHandler = $ruleHandler;
  }

  public function evaluateRuleSet(RuleSetEntity $ruleSet, NotificationContext $context): void {
    // @todo Allow implementations specific to rule set.

    foreach ($ruleSet->getRules() as $rule) {
      if ($this->ruleHandler->evaluateRule($rule, $context)) {
        if ($ruleSet->isExecuteOnlyFirstRule() || $rule->isStopAfterThisRule()) {
          break;
        }
      }
    }
  }

}
