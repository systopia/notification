<?php
declare(strict_types = 1);

class CRM_Notification_Page_EntityConfig extends CRM_Core_Page {

  public function run() {
    $entities = ['Activity', 'Contribution', 'Membership', 'Contact', 'Case'];
    $this->assign('entities', $entities);

    $ruleSets = [];
    try {
      $ruleSets = \Civi\Api4\NotificationRuleSet::get()
        ->addSelect('id', 'title', 'monitored_entity_type', 'is_active', 'is_execute_only_first_rule')
        ->addOrderBy('monitored_entity_type', 'ASC')
        ->addOrderBy('title', 'ASC')
        ->execute();
    }
    catch (\Throwable $e) {
    }

    $this->assign('ruleSets', $ruleSets ?: []);

    $ruleSetIds = [];
    foreach ($ruleSets as $rs) {
      $ruleSetIds[] = (int) $rs['id'];
    }

    $rulesBySet = [];
    if ($ruleSetIds) {
      try {
        $rules = \Civi\Api4\NotificationRule::get()
          ->addSelect('id', 'rule_set_id', 'is_active', 'title')
          ->addWhere('rule_set_id', 'IN', $ruleSetIds)
          ->addOrderBy('rule_set_id', 'ASC')
          ->addOrderBy('id', 'ASC')
          ->execute();
        foreach ($rules as $r) {
          $rulesBySet[(int) $r['rule_set_id']][] = $r;
        }
      }
      catch (\Throwable $e) {
      }
    }

    $this->assign('rulesBySet', $rulesBySet);
    $counts = [];
    foreach ($rulesBySet as $sid => $list) {
      $counts[$sid] = count($list);
    }
    $this->assign('ruleCounts', $counts);

    $byEntity = [];
    foreach ($ruleSets as $rs) {
      $byEntity[$rs['monitored_entity_type']][] = $rs;
    }
    $this->assign('ruleSetsByEntity', $byEntity);

    $res = CRM_Core_Resources::singleton();
    $res->addStyleFile('notification', 'css/entity-config.css', CRM_Core_Resources::DEFAULT_WEIGHT, 'html-header');
    $res->addScriptFile('notification', 'js/entity-config.js', CRM_Core_Resources::DEFAULT_WEIGHT, 'html-header');

    parent::run();
  }

}
