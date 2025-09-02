<?php
declare(strict_types = 1);

/**
 * GET param: rule_id
 */
class CRM_Notification_Form_EditRule extends CRM_Core_Form {

  protected $ruleId = 0;
  protected $entity = 'Activity';
  protected $ruleSetId = 0;

  public function preProcess() {
    $this->ruleId = (int) CRM_Utils_Request::retrieve('rule_id', 'Integer', $this, TRUE, 0);
    if (!$this->ruleId) {
      CRM_Core_Error::fatal(ts('Missing rule_id'));
    }
    $r = \Civi\Api4\NotificationRule::get()->addSelect('*')->addWhere('id', '=', $this->ruleId)->execute();
    if (!$r->count()) {
      CRM_Core_Error::fatal(ts('Rule not found'));
    }
    $rule = $r[0];
    $this->ruleSetId = (int) $rule['rule_set_id'];
    $rs = \Civi\Api4\NotificationRuleSet::get()->addSelect('*')->addWhere('id', '=', $this->ruleSetId)->execute();
    $this->entity = $rs[0]['monitored_entity_type'];
    $this->assign('ruleId', $this->ruleId);
    $this->assign('ruleSetId', $this->ruleSetId);
    $this->assign('entity', $this->entity);
  }

  public function buildQuickForm() {
    $this->add('text', 'title', ts('Rule Title'), [], TRUE);
    $this->add('checkbox', 'is_active', ts('Active'));
    $this->add('checkbox', 'on_create', ts('On Create'));
    $this->add('checkbox', 'on_update', ts('On Update'));
    $this->add('checkbox', 'on_delete', ts('On Delete'));
    $this->add('select', 'languages',
      ts('Languages'), CRM_Core_I18n::languages(TRUE), FALSE,
      ['class' => 'crm-select2', 'multiple' => TRUE]);

    $this->addEntityRef('contacts', ts('Contacts'), ['entity' => 'Contact', 'multiple' => TRUE]);
    $this->addEntityRef('groups', ts('Groups'), ['entity' => 'Group', 'multiple' => TRUE]);
    $this->add('text', 'contact_types_csv', ts('Contact Types CSV'));

    $this->add('select', 'field_name',
      ts('Field Name'), (new CRM_Notification_Form_EntityRuleWizard())->loadEntityFields($this->entity), TRUE,
      ['class' => 'crm-select2']);
    foreach (['operator_before', 'operator_after'] as $op) {
      $this->add('select', $op, ts(ucwords(str_replace('_', ' ', $op))),
        ['in' => 'in', 'not_in' => 'not_in', 'eq' => 'eq', 'neq' => 'neq'], TRUE);
    }
    $this->add('select', 'value_before_ids',
      ts('Value Before (options)'), [], FALSE,
      ['class' => 'crm-select2', 'multiple' => TRUE]);
    $this->add('text', 'value_before', ts('Value Before (raw)'));
    $this->add('select', 'value_after_ids',
      ts('Value After (options)'), [], FALSE,
      ['class' => 'crm-select2', 'multiple' => TRUE]);
    $this->add('text', 'value_after', ts('Value After (raw)'));

    $this->addEntityRef('message_template_id_ref', ts('Message Template'), ['entity' => 'MessageTemplate']);

    $this->setDefaults($this->loadDefaults());

    $this->addButtons([
      ['type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => ts('Cancel')],
    ]);
    parent::buildQuickForm();
  }

  protected function loadDefaults() {
    $d = [];
    $r = \Civi\Api4\NotificationRule::get()->addWhere('id', '=', $this->ruleId)->execute();
    $rule = $r[0];
    $d['title'] = $rule['title'];
    $d['is_active'] = (bool) $rule['is_active'];
    $d['on_create'] = (bool) $rule['on_create'];
    $d['on_update'] = (bool) $rule['on_update'];
    $d['on_delete'] = (bool) $rule['on_delete'];
    $d['languages'] = (array) $rule['languages'];

    $cs = \Civi\Api4\NotificationContactSelection::get()->addWhere('rule_id', '=', $this->ruleId)->execute();
    if ($cs->count()) {
      $d['contact_types_csv'] = implode(',', (array) $cs[0]['contact_type_ids']);
      if (!empty($cs[0]['contact_ids'])) {
        $d['contacts'] = array_map(function($id) {
          return ['id' => $id, 'text' => "#{$id}"];
        }, (array) $cs[0]['contact_ids']);
      }
      if (!empty($cs[0]['group_ids'])) {
        $d['groups'] = array_map(function($id) {
          return ['id' => $id, 'text' => "#{$id}"];
        }, (array) $cs[0]['group_ids']);
      }
    }

    $fm = \Civi\Api4\NotificationFieldMonitoring::get()->addWhere('rule_id', '=', $this->ruleId)->execute();
    if ($fm->count()) {
      $d['field_name'] = $fm[0]['field_name'];
      $d['operator_before'] = $fm[0]['operator_before'];
      $d['operator_after']  = $fm[0]['operator_after'];
      $d['value_before'] = $fm[0]['value_before'];
      $d['value_after']  = $fm[0]['value_after'];
      $opts = (new CRM_Notification_Form_EntityRuleWizard())->loadFieldOptions($this->entity, $d['field_name']);
      if ($opts) {
        $this->getElement('value_before_ids')->loadArray($opts);
        $this->getElement('value_after_ids')->loadArray($opts);
        foreach (['value_before', 'value_after'] as $k) {
          if (!empty($d[$k]) && preg_match('/^\[(.*)\]$/', $d[$k], $m)) {
            $ids = array_filter(array_map('intval', preg_split('/\s*,\s*/', $m[1])));
            $d[$k . '_ids'] = $ids;
          }
        }
      }
    }

    $mt = \Civi\Api4\NotificationRuleMessageTemplate::get()->addWhere('rule_id', '=', $this->ruleId)->execute();
    if ($mt->count()) {
      $d['message_template_id_ref'] = ['id' => (int) $mt[0]['msg_template_id']];
    }
    return $d;
  }

  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function postProcess() {
    // phpcs:enable
    try {
      $v = $this->exportValues();

      \Civi\Api4\NotificationRule::update()
        ->addWhere('id', '=', $this->ruleId)
        ->addValue('title', $v['title'])
        ->addValue('is_active', !empty($v['is_active']))
        ->addValue('on_create', !empty($v['on_create']))
        ->addValue('on_update', !empty($v['on_update']))
        ->addValue('on_delete', !empty($v['on_delete']))
        ->addValue('languages', isset($v['languages']) ? (array) $v['languages'] : [])
        ->execute();

      \Civi\Api4\NotificationContactSelection::delete()->addWhere('rule_id', '=', $this->ruleId)->execute();
      $contacts = [];
      if (!empty($v['contacts'])) {
        foreach ((array) $v['contacts'] as $r) {
          $contacts[] = (int) $r['id'];
        }
      }
      $groups = [];
      if (!empty($v['groups'])) {
        foreach ((array) $v['groups'] as $r) {
          $groups[] = (int) $r['id'];
        }
      }
      \Civi\Api4\NotificationContactSelection::create()
        ->addValue('rule_id', $this->ruleId)
        ->addValue('contact_ids', $contacts)
        ->addValue('group_ids', $groups)
        ->addValue('contact_type_ids', (array) $this->csvToArray(CRM_Utils_Array::value('contact_types_csv', $v, '')))
        ->execute();

      $valBefore = !empty($v['value_before_ids']) ? '[' . implode(',',
          array_map('intval', (array) $v['value_before_ids'])) . ']' :
        $this->normalizeValue(CRM_Utils_Array::value('value_before', $v, ''));
      $valAfter  = !empty($v['value_after_ids']) ? '[' . implode(',',
          array_map('intval', (array) $v['value_after_ids'])) . ']' :
        $this->normalizeValue(CRM_Utils_Array::value('value_after', $v, ''));
      $fm = \Civi\Api4\NotificationFieldMonitoring::get()->addWhere('rule_id', '=',
        $this->ruleId)->addWhere('field_name', '=', $v['field_name'])->setLimit(1)->execute();
      if ($fm->count()) {
        \Civi\Api4\NotificationFieldMonitoring::update()->addWhere('id', '=', (int) $fm[0]['id'])
          ->addValue('operator_before', $v['operator_before'])->addValue('value_before', $valBefore)
          ->addValue('operator_after', $v['operator_after'])->addValue('value_after', $valAfter)->execute();
      }
      else {
        \Civi\Api4\NotificationFieldMonitoring::create()->addValue('rule_id',
          $this->ruleId)->addValue('field_name', $v['field_name'])
          ->addValue('operator_before', $v['operator_before'])->addValue('value_before', $valBefore)
          ->addValue('operator_after', $v['operator_after'])->addValue('value_after', $valAfter)->execute();
      }

      if (!empty($v['message_template_id_ref']['id'])) {
        $mtId = (int) $v['message_template_id_ref']['id'];
        $link = \Civi\Api4\NotificationRuleMessageTemplate::get()
          ->addWhere('rule_id', '=', $this->ruleId)->setLimit(1)->execute();
        if ($link->count()) {
          \Civi\Api4\NotificationRuleMessageTemplate::update()->addWhere('id', '=', (int) $link[0]['id'])
            ->addValue('msg_template_id', $mtId)->execute();
        }
        else {
          \Civi\Api4\NotificationRuleMessageTemplate::create()->addValue('rule_id', $this->ruleId)
            ->addValue('msg_template_id', $mtId)->execute();
        }
      }

      CRM_Core_Session::setStatus(ts('Rule updated'), '', 'success');
      $url = CRM_Utils_System::url('civicrm/notification/entities', 'reset=1');
      CRM_Utils_System::redirect($url);
    }
    catch (\Throwable $e) {
      Civi::log()->error('EditRule error', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
      CRM_Core_Session::setStatus(ts('Error: %1', [1 => $e->getMessage()]), '', 'error');
    }
  }

  protected function csvToArray($csv) {
    if (!$csv) {
      return [];
    }
    $parts = preg_split('/\s*,\s*/', trim($csv));
    $out = [];
    foreach ($parts as $p) {
      if ($p !== '') {
        $out[] = $p;
      }
    }
    return $out;
  }

  protected function normalizeValue($raw) {
    $s = trim($raw);
    if ($s === '') {
      return '[]';
    }
    if (preg_match('/^\s*\[.*\]\s*$/', $s)) {
      return $s;
    }
    $ints = [];
    foreach (preg_split('/\s*,\s*/', $s) as $p) {
      $n = (int) $p;
      if ($n) {
        $ints[] = $n;
      }
    }
    return '[' . implode(',', $ints) . ']';
  }

}
