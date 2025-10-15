<?php
declare(strict_types = 1);

class CRM_Notification_Page_EntityConfig extends CRM_Core_Page {

  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function run(): void {
    // phpcs:enable
    $entities = ['Activity', 'Contribution', 'Membership', 'Contact', 'Case'];
    $this->assign('entities', $entities);

    /** @var array<int, array<string, mixed>> $ruleSetsArr */
    $ruleSetsArr = [];
    try {
      $ruleSets = \Civi\Api4\NotificationRuleSet::get()
        ->addSelect('id', 'title', 'monitored_entity_type', 'is_active', 'is_execute_only_first_rule')
        ->addOrderBy('monitored_entity_type', 'ASC')
        ->addOrderBy('title', 'ASC')
        ->execute();

      foreach ($ruleSets as $row) {
        $ruleSetsArr[] = (array) $row;
      }
    }
    catch (\Throwable $e) {
      Civi::log()->warning('EntityConfig: failed to load rule sets', ['msg' => $e->getMessage()]);
      throw $e;
    }

    $this->assign('ruleSets', $ruleSetsArr);

    // Collect RuleSet IDs
    $ruleSetIds = [];
    foreach ($ruleSetsArr as $rs) {
      // <- safe int
      $ruleSetIds[] = $this->toInt($rs['id'] ?? 0);
    }

    /** @var array<int, array<int, array<string, mixed>>> $rulesBySet */
    $rulesBySet = [];
    if (count($ruleSetIds) > 0) {
      try {
        $rules = \Civi\Api4\NotificationRule::get()
          ->addSelect('id', 'rule_set_id', 'is_active', 'title')
          ->addWhere('rule_set_id', 'IN', $ruleSetIds)
          ->addOrderBy('rule_set_id', 'ASC')
          ->addOrderBy('id', 'ASC')
          ->execute();

        foreach ($rules as $r) {
          $row = (array) $r;
          // <- safe int
          $sid = $this->toInt($row['rule_set_id'] ?? 0);
          if (!isset($rulesBySet[$sid])) {
            $rulesBySet[$sid] = [];
          }
          $rulesBySet[$sid][] = $row;
        }
      }
      catch (\Throwable $e) {
        Civi::log()->warning('EntityConfig: failed to load rules', ['msg' => $e->getMessage()]);
        throw $e;
      }
    }

    $this->assign('rulesBySet', $rulesBySet);

    // Counts per RuleSet
    $counts = [];
    foreach ($rulesBySet as $sid => $list) {
      $counts[(int) $sid] = count($list);
    }
    $this->assign('ruleCounts', $counts);

    // Group RuleSets by entity
    $byEntity = [];
    foreach ($ruleSetsArr as $rs) {
      // <- safe string
      $entity = $this->toString($rs['monitored_entity_type'] ?? '');
      if (!isset($byEntity[$entity])) {
        $byEntity[$entity] = [];
      }
      $byEntity[$entity][] = $rs;
    }
    $this->assign('ruleSetsByEntity', $byEntity);

    $res = CRM_Core_Resources::singleton();
    $res->addStyleFile('notification', 'css/entity-config.css', CRM_Core_Resources::DEFAULT_WEIGHT, 'html-header');
    $res->addScriptFile('notification', 'js/entity-config.js', CRM_Core_Resources::DEFAULT_WEIGHT, 'html-header');

    parent::run();
  }

  /**
   * ===== Helpers =====
   */
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
