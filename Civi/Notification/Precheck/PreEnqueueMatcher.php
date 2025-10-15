<?php
declare(strict_types = 1);

namespace Civi\Notification\Precheck;

use Civi\Notification\Data\NotificationContext;
use Civi\Notification\Entity\RuleEntity;
use Civi\Notification\Entity\RuleSetEntity;
use Civi\Notification\EntityService\RuleSetManager;
use Civi\Notification\Handler\RuleMatchCheckerInterface;
use Civi\Notification\Support\DbLogger;

final class PreEnqueueMatcher {

  public function __construct(
    private RuleSetManager $ruleSetManager,
    private RuleMatchCheckerInterface $ruleMatchChecker,
  ) {}

  /**
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
      $enabled = (bool) \Civi::settings()->get('notification_queue_job_enabled');

      $modeRaw = \Civi::settings()->get('notification_processing_mode');
      $mode = \is_string($modeRaw) ? $modeRaw : '';

      if (!$enabled || $mode !== 'queue') {
        return FALSE;
      }

      $ctx = $this->makeContextFromBeforeAfter($before, $after);

      $setsRaw = $this->ruleSetManager->loadRuleSetByEntityType($entity);
      /** @var array<int,RuleSetEntity|mixed> $sets */
      $sets = (array) $setsRaw;

      DbLogger::log('debug', 'pre.enqueue.rulesets', 'Loaded rulesets', [
        'entity' => $entity,
        'count'  => \count($sets),
      ]);

      foreach ($sets as $set) {
        if (!$set instanceof RuleSetEntity) {
          continue;
        }

        if (!$set->isActive()) {
          continue;
        }

        /** @var array<int,RuleEntity> $rules */
        $rules = $set->getRules();

        foreach ($rules as $rule) {
          if (!$rule instanceof RuleEntity) {
            DbLogger::log('warning', 'pre.enqueue.rule.skip', 'Rule not a RuleEntity object', [
              'class' => \is_object($rule) ? \get_class($rule) : \gettype($rule),
            ]);
            continue;
          }

          if (!$rule->isActive()) {
            continue;
          }

          $ruleId    = $rule->getId();
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
              'op'     => $op,
              'id'     => $id,
            ]);
            return TRUE;
          }
        }
      }

      DbLogger::log('info', 'pre.enqueue.result', 'Skip enqueue', [
        'entity' => $entity,
        'op'     => $op,
        'id'     => $id,
      ]);
      return FALSE;
    }
    catch (\Throwable $e) {
      DbLogger::log('error', 'pre.enqueue.exception', $e->getMessage(), [
        'type'   => \get_class($e),
        'entity' => $entity,
        'op'     => $op,
        'id'     => $id,
        'before' => $before,
        'after'  => $after,
      ]);
      throw $e;
    }
  }

  /**
   * @param array<string,mixed> $payload
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function shouldEnqueueFromPayload(array $payload): bool {
    // phpcs:enable
    $entity = $payload['entity'] ?? ($payload['type'] ?? NULL);
    if (!\is_string($entity) || $entity === '') {
      return FALSE;
    }

    $opRaw = $payload['op'] ?? ($payload['operation'] ?? 'edit');
    $op = \is_string($opRaw) ? $opRaw : 'edit';

    /** @var int|string|null $id */
    $id = $payload['id'] ?? NULL;
    /** @var array<string,mixed> $before */
    $before = \is_array($payload['before'] ?? NULL) ? $payload['before'] : [];
    /** @var array<string,mixed> $after */
    $after = \is_array($payload['after'] ?? NULL) ? $payload['after'] : [];
    /** @var array<string,mixed> $context */
    $context = \is_array($payload['context'] ?? NULL) ? $payload['context'] : [];

    return $this->shouldEnqueueFromHook($op, $entity, $id, $before, $after, $context);
  }

  /**
   * @param array<string,mixed> $before
   * @param array<string,mixed> $after
   */
  private function makeContextFromBeforeAfter(array $before, array $after): NotificationContext {
    /** @var array<string,array{0:mixed,1:mixed}> $changed */
    $changed = [];
    foreach (\array_unique(\array_merge(\array_keys($before), \array_keys($after))) as $field) {
      $old = $before[$field] ?? NULL;
      $new = $after[$field] ?? NULL;
      if ($old !== $new) {
        $changed[$field] = [$old, $new];
      }
    }

    return new NotificationContext($before, $after, $changed);
  }

}
