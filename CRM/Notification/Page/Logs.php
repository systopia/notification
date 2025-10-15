<?php
declare(strict_types = 1);

use CRM_Notification_ExtensionUtil as E;

class CRM_Notification_Page_Logs extends CRM_Core_Page {
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded
  public function run(): void {
    // phpcs:enable
    $q       = $this->toString(CRM_Utils_Request::retrieve('q', 'String', $this, FALSE, ''));
    $limit   = $this->toInt(CRM_Utils_Request::retrieve('limit', 'Positive', $this, FALSE, 50));
    $page    = $this->toInt(CRM_Utils_Request::retrieve('page', 'Positive', $this, FALSE, 1));
    $sort    = $this->toString(CRM_Utils_Request::retrieve('sort', 'String', $this, FALSE, 'created_at'));
    $order   = $this->toString(CRM_Utils_Request::retrieve('order', 'String', $this, FALSE, 'desc'));

    /** @var mixed $levelReq */
    $levelReq = CRM_Utils_Request::retrieve('level', 'Array', $this, FALSE, []);
    $rawLevels = $this->normalizeToStringArray($levelReq);

    if ($limit <= 0) {
      $limit = 50;
    }
    if ($page <= 0) {
      $page = 1;
    }

    $sortWhitelist = ['id', 'created_at', 'level', 'area', 'message'];
    if (!in_array($sort, $sortWhitelist, TRUE)) {
      $sort = 'created_at';
    }
    $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

    $levelOptions = ['debug', 'info', 'warning', 'error'];
    $levels = array_values(array_intersect($levelOptions, $rawLevels));

    $where = [];
    $args  = [];

    if ($q !== '') {
      $like = '%' . $q . '%';
      $where[] = '(message LIKE %1 OR area LIKE %1 OR context_json LIKE %1)';
      $args[1] = [$like, 'String'];
    }

    if (count($levels) > 0) {
      $in = [];
      $i = 10;
      foreach ($levels as $lv) {
        $args[$i] = [$lv, 'String'];
        $in[] = '%' . $i;
        $i++;
      }
      $where[] = 'level IN (' . implode(',', $in) . ')';
    }

    $whereSql = $where !== [] ? ('WHERE ' . implode(' AND ', $where)) : '';

    $count = (int) CRM_Core_DAO::singleValueQuery(
      "SELECT COUNT(*) FROM civicrm_notification_log {$whereSql}",
      $args
    );

    $totalPages = max(1, (int) ceil($count / $limit));
    if ($page > $totalPages) {
      $page = $totalPages;
    }
    $offset = ($page - 1) * $limit;

    $hasPrev = $page > 1;
    $hasNext = $page < $totalPages;
    $prevPage = $hasPrev ? ($page - 1) : 1;
    $nextPage = $hasNext ? ($page + 1) : $totalPages;

    $sql = "
      SELECT id, created_at, level, area, message, context_json
      FROM civicrm_notification_log
      {$whereSql}
      ORDER BY {$sort} {$order}
      LIMIT %2 OFFSET %3
    ";
    $args[2] = [$limit, 'Integer'];
    $args[3] = [$offset, 'Integer'];

    $rows = [];
    /** @var \CRM_Core_DAO&object{
     *   id:mixed,
     *   created_at:mixed,
     *   level:mixed,
     *   area:mixed,
     *   message:mixed,
     *   context_json:mixed
     * } $dao
     */
    $dao = CRM_Core_DAO::executeQuery($sql, $args);
    // @phpstan-ignore-next-line
    while ($dao->fetch()) {
      $json   = $this->toString($dao->context_json ?? '');
      $pretty = $this->prettyJson($json);
      $rows[] = [
        'id'             => $this->toInt($dao->id ?? 0),
        'created_at'     => $this->toString($dao->created_at ?? ''),
        'level'          => $this->toString($dao->level ?? ''),
        'area'           => $this->toString($dao->area ?? ''),
        'message'        => $this->toString($dao->message ?? ''),
        'contextPreview' => mb_strimwidth($pretty, 0, 120, '…', 'UTF-8'),
        'contextJson'    => $pretty,
      ];
    }

    $baseParams = [
      'q'     => $q,
      'limit' => $limit,
      'sort'  => $sort,
      'order' => $order,
    ];
    if (count($levels) > 0) {
      $baseParams['level'] = $levels;
    }
    $baseQ = http_build_query($baseParams, '', '&');

    $orderMap = [];
    foreach ($sortWhitelist as $col) {
      $orderMap[$col] = ($sort === $col && $order === 'asc') ? 'desc' : 'asc';
    }

    $selfUrl  = CRM_Utils_System::url('civicrm/notification/logs', [], TRUE, NULL, FALSE);
    $resetUrl = $selfUrl;

    // --- Smarty ---
    $this->assign('selfUrl', $selfUrl);
    $this->assign('resetUrl', $resetUrl);
    $this->assign('baseQ', $baseQ);
    $this->assign('rows', $rows);
    $this->assign('q', $q);
    $this->assign('level', $levels);
    $this->assign('levelOptions', $levelOptions);
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

    CRM_Core_Resources::singleton()->addScriptFile('notification', 'js/notification-logs.js', 0, 'page-header');

    parent::run();
  }

  private function prettyJson(string $json): string {
    if ($json === '') {
      return '';
    }
    $decoded = json_decode($json, TRUE);
    if (json_last_error() === JSON_ERROR_NONE) {
      $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      return $pretty !== FALSE ? $pretty : $json;
    }
    return $json;
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

}
