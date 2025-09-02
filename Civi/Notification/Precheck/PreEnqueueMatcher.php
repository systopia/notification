<?php
declare(strict_types = 1);

namespace Civi\Notification\Precheck;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\RuleEntity;
use Civi\Notification\EntityService\RuleSetManager;
use Civi\Notification\Handler\RuleMatchCheckerInterface;
use Civi\Notification\Support\DbLogger;

final class PreEnqueueMatcher {

  public function __construct(
    private RuleSetManager $ruleSetManager,
    private RuleMatchCheckerInterface $ruleMatchChecker,
  ) {}

  /**
   *
   * @param string $op
   * @param string $entity
   * @param int|string|null $id
   * @param array<string,mixed> $before
   * @param array<string,mixed> $after
   * @param array<string,mixed> $extraContext
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function shouldEnqueueFromHook(
    // phpcs:enable
    string $op,
    string $entity,
    int|string|null $id,
    array $before,
    array $after,
    array $extraContext = [],
  ): bool {
    try {

      if (!\Civi::settings()->get('notification_queue_job_enabled') ||
        \Civi::settings()->get('notification_processing_mode') != 'queue') {
        return FALSE;
      }

      $ctx = $this->makeContextFromBeforeAfter($before, $after);

      $sets = $this->ruleSetManager->loadRuleSetByEntityType($entity);

      DbLogger::log('debug', 'pre.enqueue.rulesets', 'Loaded rulesets', [
        'entity' => $entity,
        'count'  => \is_countable($sets) ? \count($sets) : 0,
      ]);

      foreach ($sets as $set) {

        $isActive = \is_object($set) && \method_exists($set, 'isActive')
          ? (bool) $set->isActive()
          : FALSE;
        if (!$isActive) {
          continue;
        }

        $rules = (\is_object($set) && \method_exists($set, 'getRules'))
          ? (array) $set->getRules()
          : [];

        foreach ($rules as $rule) {

          if (!$rule instanceof RuleEntity) {
            DbLogger::log('warning', 'pre.enqueue.rule.skip', 'Rule not a RuleEntity object', [
              'class' => \is_object($rule) ? \get_class($rule) : gettype($rule),
            ]);
            continue;
          }

          $ruleActive = \method_exists($rule, 'isActive') ? (bool) $rule->isActive() : FALSE;
          if (!$ruleActive) {
            continue;
          }

          $ruleId    = \method_exists($rule, 'getId') ? (int) $rule->getId() : 0;
          $ruleTitle = \method_exists($rule, 'getTitle') ? (string) $rule->getTitle() : '';

          $matched = $this->ruleMatchChecker->isRuleMatched($rule, $ctx);

          DbLogger::log('debug', 'pre.enqueue.rule', 'Evaluated rule', [
            'rule_id' => $ruleId,
            'title'   => $ruleTitle,
            'matched' => (int) $matched,
          ]);

          if ($matched) {
            DbLogger::log('info', 'pre.enqueue.result', 'Will enqueue', [
              'entity' => $entity,
              'op' => $op,
              'id' => $id,
            ]);
            return TRUE;
          }
        }
      }

      DbLogger::log('info', 'pre.enqueue.result', 'Skip enqueue', [
        'entity' => $entity,
        'op' => $op,
        'id' => $id,
      ]);
      return FALSE;
    }
    catch (\Throwable $e) {

      DbLogger::log('error', 'pre.enqueue.exception', $e->getMessage(), [
        'type'    => \get_class($e),
        'entity'  => $entity,
        'op'      => $op,
        'id'      => $id,
        'before'  => $before,
        'after'   => $after,
      ]);
      return TRUE;
    }
  }

  public function shouldEnqueueFromPayload(array $payload): bool {
    $entity = $payload['entity'] ?? $payload['type'] ?? NULL;
    if (!$entity) {
      return FALSE;
    }
    $op      = (string) ($payload['op'] ?? $payload['operation'] ?? 'edit');
    $id      = $payload['id'] ?? NULL;
    $before  = (array) ($payload['before'] ?? []);
    $after   = (array) ($payload['after'] ?? []);
    $context = (array) ($payload['context'] ?? []);

    return $this->shouldEnqueueFromHook($op, $entity, $id, $before, $after, $context);
  }

  /**
   *
   * @param array<string,mixed> $before
   * @param array<string,mixed> $after
   */
  private function makeContextFromBeforeAfter(array $before, array $after): NotificationContext {
    $changed = [];
    foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $field) {
      $old = $before[$field] ?? NULL;
      $new = $after[$field] ?? NULL;
      if ($old !== $new) {
        $changed[$field] = [$old, $new];
      }
    }

    // Tu NotificationContext real espera (oldValues, newValues, changeSet)
    return new NotificationContext($before, $after, $changed);
  }

}
