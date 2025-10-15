<?php
declare(strict_types = 1);

namespace Civi\Notification\Handler;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\RuleSetEntity;
use Civi\Notification\Support\DbLogger;

final class RuleSetHandler implements RuleSetHandlerInterface {

  public function __construct(private RuleHandlerInterface $ruleHandler) {}

  public function evaluateRuleSet(RuleSetEntity $ruleSet, NotificationContext $context): void {
    /** @var list<\Civi\Notification\Entity\RuleEntity> $rules */
    $rules = $ruleSet->getRules();
    $rsId  = $ruleSet->getId();

    DbLogger::log('info', 'ruleset.start', 'Evaluating ruleset', [
      'ruleset_id'   => $rsId,
      'rules_count'  => count($rules),
    ]);

    foreach ($rules as $rule) {
      /** @var \Civi\Notification\Entity\RuleEntity $rule */
      $ruleId = $rule->getId();
      $title = method_exists($rule, 'getTitle') ? (string) $rule->getTitle() : '';

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
        throw $e;
      }
    }

    DbLogger::log('info', 'ruleset.done', 'Ruleset evaluation finished', [
      'ruleset_id' => $rsId,
    ]);
  }

}
