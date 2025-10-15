<?php
declare(strict_types = 1);

class CRM_Notification_Page_Queue extends CRM_Core_Page {
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded
  public function run(): void {
    // phpcs:enable
    $queueName = 'notification.jobs';

    $q       = $this->toString(CRM_Utils_Request::retrieve('q', 'String', $this, FALSE, ''));
    $limit   = $this->toInt(CRM_Utils_Request::retrieve('limit', 'Positive', $this, FALSE, 50));
    $page    = $this->toInt(CRM_Utils_Request::retrieve('page', 'Positive', $this, FALSE, 1));
    $sort    = $this->toString(CRM_Utils_Request::retrieve('sort', 'String', $this, FALSE, 'submit_time'));
    $order   = $this->toString(CRM_Utils_Request::retrieve('order', 'String', $this, FALSE, 'desc'));

    $statusOptions = ['pending', 'processed'];
    /** @var mixed $statusReq */
    $statusReq = CRM_Utils_Request::retrieve('status', 'Array', $this, FALSE, []);
    $rawStatuses = $this->normalizeToStringArray($statusReq);
    $statuses = array_values(array_intersect($statusOptions, $rawStatuses));

    // Defaults
    if ($limit <= 0) {
      $limit = 50;
    }
    if ($page <= 0) {
      $page = 1;
    }

    $sortWhitelist = ['id', 'submit_time', 'release_time', 'run_count', 'weight'];
    if (!in_array($sort, $sortWhitelist, TRUE)) {
      $sort = 'submit_time';
    }
    $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

    // --- WHERE / ARGS ---
    $where = ['queue_name = %1'];
    $args  = [1 => [$queueName, 'String']];

    if ($q !== '') {
      $like = '%' . $q . '%';
      $where[] = '(data LIKE %2 OR queue_name LIKE %2)';
      $args[2] = [$like, 'String'];
    }

    if (count($statuses) > 0) {
      $conds = [];
      foreach ($statuses as $st) {
        if ($st === 'pending') {
          $conds[] = 'run_count = 0';
        }
        if ($st === 'processed') {
          $conds[] = 'run_count > 0';
        }
      }
      if ($conds !== []) {
        $where[] = '(' . implode(' OR ', $conds) . ')';
      }
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $count = (int) CRM_Core_DAO::singleValueQuery(
      "SELECT COUNT(*) FROM civicrm_queue_item {$whereSql}",
      $args
    );

    $totalPages = max(1, (int) ceil($count / $limit));
    if ($page > $totalPages) {
      $page = $totalPages;
    }
    $offset  = ($page - 1) * $limit;
    $hasPrev = $page > 1;
    $hasNext = $page < $totalPages;
    $prevPage = $hasPrev ? ($page - 1) : 1;
    $nextPage = $hasNext ? ($page + 1) : $totalPages;

    $sql = "
      SELECT id, queue_name, weight, submit_time, release_time, run_count, data
      FROM civicrm_queue_item
      {$whereSql}
      ORDER BY {$sort} {$order}
      LIMIT %3 OFFSET %4
    ";
    $args[3] = [$limit, 'Integer'];
    $args[4] = [$offset, 'Integer'];

    $rows = [];
    /** @var \CRM_Core_DAO&object{
     *   id:mixed,
     *   submit_time:mixed,
     *   release_time:mixed,
     *   run_count:mixed,
     *   data:mixed
     * } $dao
     */
    $dao = CRM_Core_DAO::executeQuery($sql, $args);
    // @phpstan-ignore-next-line fetch() exists on CRM_Core_DAO at runtime
    while ($dao->fetch()) {
      $payloadRaw = $this->toString($dao->data ?? '');

      $payload = @unserialize($payloadRaw);
      $callback  = NULL;
      $arguments = NULL;

      if (is_array($payload)) {
        $callback  = $payload['callback'] ?? NULL;
        $arguments = $payload['arguments'] ?? NULL;
      }
      elseif (is_object($payload)) {
        if (isset($payload->callback)) {
          $callback = $payload->callback;
        }
        if (isset($payload->arguments)) {
          $arguments = $payload->arguments;
        }
      }

      $out = ['callback' => $callback, 'arguments' => $arguments];
      $pretty  = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      $pretty  = $pretty !== FALSE ? $pretty : '{}';
      $preview = mb_strimwidth($pretty, 0, 120, '…', 'UTF-8');

      $runCount   = $this->toInt($dao->run_count ?? 0);
      $submitted  = $this->formatDateOrEmpty($this->toString($dao->submit_time ?? ''));
      $released   = $this->formatDateOrEmpty($this->toString($dao->release_time ?? ''));

      $rows[] = [
        'id'          => $this->toInt($dao->id ?? 0),
        'submitted'   => $submitted,
        'release'     => $released,
        'statusText'  => ($runCount > 0)
        ? ts('Processed (%1)', [1 => $runCount])
        : ts('Pending'),
        'preview'     => $preview,
        'payloadJson' => $pretty,
      ];
    }

    $baseParams = [
      'q'     => $q,
      'limit' => $limit,
      'sort'  => $sort,
      'order' => $order,
    ];
    if (count($statuses) > 0) {
      $baseParams['status'] = $statuses;
    }
    $baseQ = http_build_query($baseParams, '', '&');

    $orderMap = [];
    foreach ($sortWhitelist as $col) {
      $orderMap[$col] = ($sort === $col && $order === 'asc') ? 'desc' : 'asc';
    }

    $selfUrl  = CRM_Utils_System::url('civicrm/notification/queue', [], TRUE, NULL, FALSE);
    $resetUrl = $selfUrl;

    // Smarty
    $this->assign('selfUrl', $selfUrl);
    $this->assign('resetUrl', $resetUrl);
    $this->assign('baseQ', $baseQ);

    $this->assign('rows', $rows);
    $this->assign('queueName', $queueName);

    $this->assign('q', $q);
    $this->assign('statusOptions', $statusOptions);
    $this->assign('status', $statuses);

    $this->assign('limit', $limit);
    $this->assign('limitOptions', [20, 50, 100, 200]);

    $this->assign('page', $page);
    $this->assign('prevPage', $prevPage);
    $this->assign('nextPage', $nextPage);
    $this->assign('hasPrev', $hasPrev);
    $this->assign('hasNext', $hasNext);
    $this->assign('totalPages', $totalPages);

    $this->assign('orderMap', $orderMap);
    $this->assign('sort', $sort);
    $this->assign('order', $order);

    $this->assign('count', $count);
    $this->assign('fromItem', ($count > 0) ? ($offset + 1) : 0);
    $this->assign('toItem', min($offset + $limit, $count));

    $res = CRM_Core_Resources::singleton();
    $res->addScriptFile('notification', 'templates/CRM/Notification/Page/queue.js', 100, 'page-footer');
    $res->addStyleFile('notification', 'templates/CRM/Notification/Page/queue.css', 100);

    parent::run();
  }

  /* ===== Helpers ===== */

  /**
   * @return array<int,string>
   */
  private function normalizeToStringArray(mixed $v): array {
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveArrayIterator((array) $v));
    foreach ($it as $val) {
      $s = $this->toString($val);
      if ($s !== '') {
        $out[] = $s;
      }
    }
    return array_values(array_unique($out));
  }

  private function toInt(mixed $value): int {
    if (is_int($value)) {
      return $value;
    }
    if (is_float($value)) {
      return (int) $value;
    }
    if (is_string($value)) {
      $v = trim($value);
      if ($v !== '' && preg_match('/^-?\d+$/', $v) === 1) {
        return (int) $v;
      }
    }
    return 0;
  }

  private function toString(mixed $value): string {
    if (is_string($value)) {
      return $value;
    }
    if (is_int($value) || is_float($value)) {
      return (string) $value;
    }
    return '';
  }

  private function formatDateOrEmpty(string $raw): string {
    if ($raw === '') {
      return '';
    }
    $ts = strtotime($raw);
    if ($ts === FALSE) {
      return '';
    }
    return date('Y-m-d H:i:s', $ts);
  }

}
