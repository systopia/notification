<?php
declare(strict_types = 1);

namespace Civi\Notification\Runner;

use Civi\Notification\DTO\Change;
use Civi\Notification\DTO\ChangeSet;
use Civi\Notification\DTO\NotificationEvent;
use Civi\Notification\Handler\RuleSetHandler;

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
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded,
  public function handle(array $payload): void {
    // phpcs:enable
    self::touch($this->ruleSetHandler);

    try {
      $type = $payload['type'] ?? NULL;
      if ($type !== 'notification.event') {
        if ($this->logger !== NULL && method_exists($this->logger, 'warning')) {
          $this->logger->warning('Unknown payload type', ['type' => $type]);
        }
        return;
      }

      $changes = new ChangeSet();
      $rawChanges = $payload['changes'] ?? [];
      if (is_array($rawChanges)) {
        foreach ($rawChanges as $chg) {
          if (is_array($chg) && isset($chg['field'])) {
            $field = is_string($chg['field']) ? $chg['field'] : (string) $chg['field'];
            $before = $chg['before'] ?? NULL;
            $after = $chg['after'] ?? NULL;
            $changes->add(new Change($field, $before, $after));
          }
        }
      }

      $entity = isset($payload['entity']) && is_string($payload['entity']) ? $payload['entity'] : '';
      $op = isset($payload['op']) && is_string($payload['op']) ? $payload['op'] : '';

      $idVal = $payload['id'] ?? 0;
      $id = is_int($idVal) ? $idVal : (is_numeric($idVal) ? (int) $idVal : 0);

      $beforeArr = (isset($payload['before']) && is_array($payload['before'])) ? $payload['before'] : [];
      /** @var array<string,mixed> $beforeArr */
      $afterArr = (isset($payload['after']) && is_array($payload['after'])) ? $payload['after'] : [];
      /** @var array<string,mixed> $afterArr */
      $contextArr = (isset($payload['context']) && is_array($payload['context'])) ? $payload['context'] : [];
      /** @var array<string,mixed> $contextArr */

      $event = new NotificationEvent(
        $entity,
        $op,
        $id,
        $beforeArr,
        $afterArr,
        $changes,
        $contextArr
      );

    }
    // @phpstan-ignore-next-line dead catch until evaluate is wired and/or throws are modeled
    catch (\Throwable $e) {
      if ($this->logger !== NULL && method_exists($this->logger, 'error')) {
        $this->logger->error('Error processing notification payload', ['exception' => $e]);
      }
      throw $e;
    }
  }

  private static function touch(mixed $x): void {}

}
