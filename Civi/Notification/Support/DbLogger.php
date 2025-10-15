<?php
declare(strict_types = 1);

namespace Civi\Notification\Support;

final class DbLogger {

  /**
   * @param array<string, mixed> $context
   * @throws \Throwable
   */
  public static function log(string $level, string $area, string $message, array $context = []): void {

    try {
      \Civi::log()->log($level, "[notification][$area] {$message}", $context);
    }
    catch (\Throwable $e) {
      error_log(sprintf('DbLogger:CiviLog failed: %s', $e->getMessage()));
      throw $e;
    }

    try {
      static $hasTable = NULL;
      if ($hasTable === NULL) {
        $hasTable = (bool) \CRM_Core_DAO::singleValueQuery(
          "SHOW TABLES LIKE 'civicrm_notification_log'"
        );
      }
      if ($hasTable === FALSE) {
        return;
      }

      $ctxJson = json_encode(
        self::sanitize($context),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
      );
      $ctxJson = ($ctxJson !== FALSE) ? $ctxJson : '{}';

      \CRM_Core_DAO::executeQuery(
        'INSERT INTO civicrm_notification_log
                 (created_at, level, area, message, context_json)
                 VALUES (NOW(), %1, %2, %3, %4)',
        [
          1 => [$level, 'String'],
          2 => [$area, 'String'],
          3 => [$message, 'String'],
          4 => [$ctxJson, 'String'],
        ]
      );
    }
    catch (\Throwable $e) {
      error_log(sprintf('DbLogger:DB insert failed: %s', $e->getMessage()));
      throw $e;
    }
  }

  /**
   * @param array<string, mixed> $ctx
   * @return array<string, mixed>
   */
  private static function sanitize(array $ctx): array {
    foreach ($ctx as $k => $v) {
      if ($v instanceof \Throwable) {
        $ctx[$k] = [
          'type'    => get_class($v),
          'message' => $v->getMessage(),
          'file'    => $v->getFile() . ':' . $v->getLine(),
          'trace'   => $v->getTraceAsString(),
        ];
      }
      elseif (is_object($v)) {
        $ctx[$k] = ['object' => get_class($v)];
      }
      elseif (is_resource($v)) {
        /** @var resource $v */
        $ctx[$k] = ['resource' => get_resource_type($v)];
      }
    }
    return $ctx;
  }

}
