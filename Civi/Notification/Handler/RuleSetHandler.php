<?php
declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\RuleSetEntity;
use Civi\Notification\Support\DbLogger;

final class RuleSetHandler implements RuleSetHandlerInterface {

  public function __construct(private RuleHandlerInterface $ruleHandler) {}

  public function evaluateRuleSet(RuleSetEntity $ruleSet, NotificationContext $context): void {
    $rules = method_exists($ruleSet, 'getRules') ? (array) $ruleSet->getRules() : [];
    $rsId = method_exists($ruleSet, 'getId') ? $ruleSet->getId() : NULL;

    DbLogger::log('info', 'ruleset.start', 'Evaluating ruleset', [
      'ruleset_id' => $rsId,
      'rules_count' => count($rules),
    ]);

    foreach ($rules as $rule) {
      $ruleId = method_exists($rule, 'getId') ? $rule->getId() : NULL;
      $title  = method_exists($rule, 'getTitle') ? $rule->getTitle() : NULL;

      try {
        DbLogger::log('debug', 'ruleset.rule', 'Checking rule', [
          'ruleset_id' => $rsId,
          'rule_id'    => $ruleId,
          'title'      => $title,
        ]);

        $matched = $this->ruleHandler->evaluateRule($rule, $context);

        DbLogger::log($matched ? 'info' : 'debug', 'ruleset.rule.result',
          $matched ? 'Rule matched' : 'Rule not matched',
          ['ruleset_id' => $rsId, 'rule_id' => $ruleId]
        );
      }
      catch (\Throwable $e) {
        DbLogger::log('error', 'ruleset.rule', 'Rule evaluation exception', [
          'ruleset_id' => $rsId,
          'rule_id'    => $ruleId,
          'exception'  => $e,
        ]);
      }
    }

    DbLogger::log('info', 'ruleset.done', 'Ruleset evaluation finished', [
      'ruleset_id' => $rsId,
    ]);
  }

}
