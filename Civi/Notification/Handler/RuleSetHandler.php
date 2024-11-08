<?php

declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Entity\RuleSetEntity;
use Civi\Notification\Interface\RuleSetHandlerInterface;

class RuleSetHandler implements RuleSetHandlerInterface {

  private RuleHandler $ruleHandler;

  public function __construct() {
    $this->ruleHandler = new RuleHandler();
  }

  public function evaluateRuleSet(RuleSetEntity $ruleSet, array $newValues, array $oldValues): void {
    // TODO: Add logic to check if rule set entity conditions are met (source_entity_type, source_entity_id)

    foreach ($ruleSet->getRules() as $rule) {
      if ($this->ruleHandler->evaluateRule($rule, $newValues, $oldValues)) {
        if ($ruleSet->isExecuteOnlyFirstRule() || $rule->isStopAfterThisRule()) {
          break;
        }
      }
    }
  }

}
