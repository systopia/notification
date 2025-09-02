<?php
declare(strict_types = 1);

namespace Civi\Notification\Runner;

use Civi\Notification\Handler\RuleSetHandler;
use Civi\Notification\Data\NotificationContext;

final class QueueRunner {

  /**
   * @param object|null $logger
   */
  public function __construct(
    private RuleSetHandler $ruleSetHandler,
    private ?object $logger = NULL
  ) {}

  /**
   * @param array<string,mixed> $payload
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded
  public function handle(array $payload): void {
    // phpcs:enable

    \Civi\Notification\Support\DbLogger::log('info', 'queue.handle.start', 'Runner handling payload', [
      'keys' => array_keys((array) $payload),
    ]);

    self::touch($this->ruleSetHandler);

    try {
      $type = $payload['type'] ?? NULL;
      if ($type !== 'notification.event') {
        if ($this->logger !== NULL && method_exists($this->logger, 'warning')) {
          $this->logger->warning('Unknown payload type', ['type' => $type]);
        }
        return;
      }

      $entity = isset($payload['entity']) && is_string($payload['entity']) ? $payload['entity'] : '';
      $op     = isset($payload['op']) && is_string($payload['op']) ? $payload['op'] : '';
      $idVal  = $payload['id'] ?? 0;
      $id     = is_int($idVal) ? $idVal : (is_numeric($idVal) ? (int) $idVal : 0);

      /** @var array<string,mixed> $beforeArr */
      $beforeArr = (isset($payload['before']) && is_array($payload['before'])) ? $payload['before'] : [];
      /** @var array<string,mixed> $afterArr */
      $afterArr = (isset($payload['after']) && is_array($payload['after'])) ? $payload['after'] : [];
      /** @var array<string,mixed> $contextArr */
      $contextArr = (isset($payload['context']) && is_array($payload['context'])) ? $payload['context'] : [];

      $changesArr = [];
      $rawChanges = $payload['changes'] ?? NULL;

      if (is_array($rawChanges) && $rawChanges) {

        foreach ($rawChanges as $chg) {
          if (is_array($chg) && isset($chg['field'])) {
            $changesArr[] = [
              'field'  => is_string($chg['field']) ? $chg['field'] : (string) $chg['field'],
              'before' => $chg['before'] ?? NULL,
              'after'  => $chg['after'] ?? NULL,
            ];
          }
        }
      }
      else {

        foreach (array_unique(array_merge(array_keys($beforeArr), array_keys($afterArr))) as $k) {
          $b = $beforeArr[$k] ?? NULL;
          $a = $afterArr[$k] ?? NULL;
          if ($b !== $a) {
            $changesArr[] = [
              'field'  => (string) $k,
              'before' => $b,
              'after'  => $a,
            ];
          }
        }
      }

      $context = new NotificationContext(
        $beforeArr,
        $afterArr,
        $changesArr,
        $entity,
        $op,
        $id,
        $contextArr
      );

      /** @var \Civi\Notification\EntityService\RuleSetManager $em */
      $em = \Civi::service('notification.entity_manager');
      $ruleSets = $em->loadRuleSetByEntityType($entity);

      foreach ($ruleSets as $ruleSet) {
        if (method_exists($ruleSet, 'isActive') && !$ruleSet->isActive()) {
          continue;
        }
        $this->ruleSetHandler->evaluateRuleSet($ruleSet, $context);
      }

      \Civi\Notification\Support\DbLogger::log('info', 'queue.handle.done', 'Runner completed', [
        'entity' => $entity,
        'op'     => $op,
        'id'     => $id,
      ]);
    }
    catch (\Throwable $e) {
      \Civi\Notification\Support\DbLogger::log('error', 'queue.handle', 'Runner exception', [
        'exception' => ['type' => get_class($e), 'message' => $e->getMessage()],
      ]);
      if ($this->logger !== NULL && method_exists($this->logger, 'error')) {
        $this->logger->error('Error processing notification payload', ['exception' => $e]);
      }
      throw $e;
    }
  }

  private static function touch(mixed $x): void {}

}
