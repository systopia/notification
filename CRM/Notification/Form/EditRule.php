<?php
declare(strict_types = 1);

/**
 * @method void setDefaults(array $defaults)
 * @method mixed getElement(string $name)
 * @method array exportValues()
 */
class CRM_Notification_Form_EditRule extends CRM_Core_Form {

  protected int $ruleId = 0;
  protected string $entity = 'Activity';
  protected int $ruleSetId = 0;

  public function preProcess(): void {
    $rid = CRM_Utils_Request::retrieve('rule_id', 'Integer', $this, TRUE, 0);
    $this->ruleId = $this->toInt($rid);
    if ($this->ruleId === 0) {
      CRM_Core_Error::statusBounce(ts('Missing rule_id'));
    }

    $r = \Civi\Api4\NotificationRule::get()->addSelect('*')->addWhere('id', '=', $this->ruleId)->execute();
    if ($r->count() === 0) {
      CRM_Core_Error::statusBounce(ts('Rule not found'));
    }
    $rule = $this->firstRow($r);
    $this->ruleSetId = $this->toInt($rule['rule_set_id'] ?? 0);

    $rs = \Civi\Api4\NotificationRuleSet::get()->addSelect('*')->addWhere('id', '=', $this->ruleSetId)->execute();
    $rsRow = $this->firstRow($rs);
    $this->entity = $this->toString($rsRow['monitored_entity_type'] ?? 'Activity');

    $this->assign('ruleId', $this->ruleId);
    $this->assign('ruleSetId', $this->ruleSetId);
    $this->assign('entity', $this->entity);
  }

  public function buildQuickForm(): void {
    $this->add('text', 'title', ts('Rule Title'), [], TRUE);
    $this->add('checkbox', 'is_active', ts('Active'));
    $this->add('checkbox', 'on_create', ts('On Create'));
    $this->add('checkbox', 'on_update', ts('On Update'));
    $this->add('checkbox', 'on_delete', ts('On Delete'));
    $this->add('select', 'languages', ts('Languages'),
      CRM_Core_I18n::languages(TRUE), FALSE, ['class' => 'crm-select2', 'multiple' => TRUE]);
    $this->addEntityRef('contacts', ts('Contacts'), ['entity' => 'Contact', 'multiple' => TRUE]);
    $this->addEntityRef('groups', ts('Groups'), ['entity' => 'Group', 'multiple' => TRUE]);
    $this->add('text', 'contact_types_csv', ts('Contact Types CSV'));

    $entityRuleWizard = new CRM_Notification_Form_EntityRuleWizard();
    $fieldOptions = $this->loadEntityFieldsSafe($entityRuleWizard, $this->entity);

    $this->add('select', 'field_name', ts('Field Name'), $fieldOptions, TRUE, ['class' => 'crm-select2']);

    foreach (['operator_before', 'operator_after'] as $op) {
      $this->add('select', $op, ts(ucwords(str_replace('_', ' ', $op))),
        ['in' => 'in', 'not_in' => 'not_in', 'eq' => 'eq', 'neq' => 'neq'], TRUE);
    }

    $this->add('select', 'value_before_ids', ts('Value Before (options)'),
      [], FALSE, ['class' => 'crm-select2', 'multiple' => TRUE]);
    $this->add('text', 'value_before', ts('Value Before (raw)'));
    $this->add('select', 'value_after_ids', ts('Value After (options)'),
      [], FALSE, ['class' => 'crm-select2', 'multiple' => TRUE]);
    $this->add('text', 'value_after', ts('Value After (raw)'));
    $this->addEntityRef('message_template_id_ref', ts('Message Template'), ['entity' => 'MessageTemplate']);

    $this->setDefaults($this->loadDefaults());

    $this->addButtons([
      ['type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => ts('Cancel')],
    ]);
    parent::buildQuickForm();
  }

  /**
   * @return array<string,mixed>
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded
  protected function loadDefaults(): array {
    $d = [];

    $r = \Civi\Api4\NotificationRule::get()->addWhere('id', '=', $this->ruleId)->execute();
    $rule = $this->firstRow($r);
    $d['title'] = $this->toString($rule['title'] ?? '');
    $d['is_active'] = $this->toBool($rule['is_active'] ?? FALSE);
    $d['on_create'] = $this->toBool($rule['on_create'] ?? FALSE);
    $d['on_update'] = $this->toBool($rule['on_update'] ?? FALSE);
    $d['on_delete'] = $this->toBool($rule['on_delete'] ?? FALSE);
    $d['languages'] = $this->toStringArray($rule['languages'] ?? []);

    $cs = \Civi\Api4\NotificationContactSelection::get()->addWhere('rule_id', '=', $this->ruleId)->execute();
    if ($cs->count() > 0) {
      $csRow = $this->firstRow($cs);
      $d['contact_types_csv'] = implode(',', $this->toStringArray($csRow['contact_type_ids'] ?? []));
      $contactIds = $this->toIntArray($csRow['contact_ids'] ?? []);
      if ($contactIds !== []) {
        $d['contacts'] = array_map(static function (int $id): array {
          return ['id' => $id, 'text' => "#{$id}"];
        }, $contactIds);
      }
      $groupIds = $this->toIntArray($csRow['group_ids'] ?? []);
      if ($groupIds !== []) {
        $d['groups'] = array_map(static function (int $id): array {
          return ['id' => $id, 'text' => "#{$id}"];
        }, $groupIds);
      }
    }

    $fm = \Civi\Api4\NotificationFieldMonitoring::get()->addWhere('rule_id', '=', $this->ruleId)->execute();
    if ($fm->count() > 0) {
      $fmRow = $this->firstRow($fm);
      $d['field_name'] = $this->toString($fmRow['field_name'] ?? '');
      $d['operator_before'] = $this->toString($fmRow['operator_before'] ?? '');
      $d['operator_after']  = $this->toString($fmRow['operator_after'] ?? '');
      $d['value_before'] = $this->toString($fmRow['value_before'] ?? '');
      $d['value_after']  = $this->toString($fmRow['value_after'] ?? '');

      $entityRuleWizard = new CRM_Notification_Form_EntityRuleWizard();
      $opts = $this->loadFieldOptionsSafe($entityRuleWizard, $this->entity, $d['field_name']);
      if ($opts !== []) {
        $elBefore = $this->getElement('value_before_ids');
        $elAfter  = $this->getElement('value_after_ids');
        if (is_object($elBefore) && method_exists($elBefore, 'loadArray')) {
          $elBefore->loadArray($opts);
        }
        if (is_object($elAfter) && method_exists($elAfter, 'loadArray')) {
          $elAfter->loadArray($opts);
        }
        foreach (['value_before', 'value_after'] as $k) {
          $val = $this->toString($d[$k] ?? '');
          if ($val !== '' && preg_match('/^\[(.*)\]$/', $val, $m) === 1) {
            $parts = preg_split('/\s*,\s*/', $m[1]);
            $parts = $parts === FALSE ? [] : $parts;
            $ints = array_map('intval', $parts);
            $ids = array_values(array_filter($ints, static fn (int $n): bool => $n > 0));
            $d[$k . '_ids'] = $ids;
          }
        }
      }
    }

    $mt = \Civi\Api4\NotificationRuleMessageTemplate::get()->addWhere('rule_id', '=', $this->ruleId)->execute();
    if ($mt->count() > 0) {
      $mtRow = $this->firstRow($mt);
      $mtId = $this->toInt($mtRow['msg_template_id'] ?? 0);
      if ($mtId > 0) {
        $d['message_template_id_ref'] = ['id' => $mtId];
      }
    }

    return $d;
  }

  // phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded
  public function postProcess(): void {
    try {
      $v = $this->exportValues();

      \Civi\Api4\NotificationRule::update()
        ->addWhere('id', '=', $this->ruleId)
        ->addValue('title', $this->toString($v['title'] ?? ''))
        ->addValue('is_active', $this->toBool($v['is_active'] ?? FALSE))
        ->addValue('on_create', $this->toBool($v['on_create'] ?? FALSE))
        ->addValue('on_update', $this->toBool($v['on_update'] ?? FALSE))
        ->addValue('on_delete', $this->toBool($v['on_delete'] ?? FALSE))
        ->addValue('languages', $this->toStringArray($v['languages'] ?? []))
        ->execute();

      \Civi\Api4\NotificationContactSelection::delete()->addWhere('rule_id', '=', $this->ruleId)->execute();

      $contacts = [];
      if (isset($v['contacts']) && is_array($v['contacts']) && $v['contacts'] !== []) {
        foreach ($v['contacts'] as $r) {
          if (is_array($r) && isset($r['id'])) {
            $contacts[] = $this->toInt($r['id']);
          }
        }
      }

      $groups = [];
      if (isset($v['groups']) && is_array($v['groups']) && $v['groups'] !== []) {
        foreach ($v['groups'] as $r) {
          if (is_array($r) && isset($r['id'])) {
            $groups[] = $this->toInt($r['id']);
          }
        }
      }

      \Civi\Api4\NotificationContactSelection::create()
        ->addValue('rule_id', $this->ruleId)
        ->addValue('contact_ids', $contacts)
        ->addValue('group_ids', $groups)
        ->addValue('contact_type_ids', $this->csvToArray($this->toString($v['contact_types_csv'] ?? '')))
        ->execute();

      $valueBeforeIds = isset($v['value_before_ids'])
      && is_array($v['value_before_ids']) ? $this->toIntArray($v['value_before_ids']) : [];
      $valueAfterIds  = isset($v['value_after_ids'])
      && is_array($v['value_after_ids']) ? $this->toIntArray($v['value_after_ids']) : [];

      $valBefore = $valueBeforeIds !== [] ? '[' . implode(',',
          array_values(array_filter($valueBeforeIds, static fn (int $n): bool => $n > 0))
        ) . ']' : $this->normalizeValue($this->toString($v['value_before'] ?? ''));
      $valAfter  = $valueAfterIds !== [] ? '[' . implode(',',
          array_values(array_filter($valueAfterIds, static fn (int $n): bool => $n > 0))
        ) . ']' : $this->normalizeValue($this->toString($v['value_after'] ?? ''));

      $fieldName = $this->toString($v['field_name'] ?? '');

      $fm = \Civi\Api4\NotificationFieldMonitoring::get()
        ->addWhere('rule_id', '=', $this->ruleId)
        ->addWhere('field_name', '=', $fieldName)
        ->setLimit(1)
        ->execute();

      if ($fm->count() > 0) {
        $fmRow = $this->firstRow($fm);
        $fmId = $this->toInt($fmRow['id'] ?? 0);

        \Civi\Api4\NotificationFieldMonitoring::update()
          ->addWhere('id', '=', $fmId)
          ->addValue('operator_before', $this->toString($v['operator_before'] ?? ''))
          ->addValue('value_before', $valBefore)
          ->addValue('operator_after', $this->toString($v['operator_after'] ?? ''))
          ->addValue('value_after', $valAfter)
          ->execute();
      }
      else {
        \Civi\Api4\NotificationFieldMonitoring::create()
          ->addValue('rule_id', $this->ruleId)
          ->addValue('field_name', $fieldName)
          ->addValue('operator_before', $this->toString($v['operator_before'] ?? ''))
          ->addValue('value_before', $valBefore)
          ->addValue('operator_after', $this->toString($v['operator_after'] ?? ''))
          ->addValue('value_after', $valAfter)
          ->execute();
      }

      $mtId = 0;
      if (isset($v['message_template_id_ref'])
        && is_array($v['message_template_id_ref']) && isset($v['message_template_id_ref']['id'])) {
        $mtId = $this->toInt($v['message_template_id_ref']['id']);
      }

      if ($mtId > 0) {
        $link = \Civi\Api4\NotificationRuleMessageTemplate::get()->addWhere('rule_id', '=',
          $this->ruleId)->setLimit(1)->execute();
        if ($link->count() > 0) {
          $linkRow = $this->firstRow($link);
          $linkId = $this->toInt($linkRow['id'] ?? 0);
          \Civi\Api4\NotificationRuleMessageTemplate::update()->addWhere('id', '=',
            $linkId)->addValue('msg_template_id', $mtId)->execute();
        }
        else {
          \Civi\Api4\NotificationRuleMessageTemplate::create()->addValue('rule_id',
            $this->ruleId)->addValue('msg_template_id', $mtId)->execute();
        }
      }

      CRM_Core_Session::setStatus(ts('Rule updated'), '', 'success');
      $url = CRM_Utils_System::url('civicrm/notification/entities', 'reset=1');
      CRM_Utils_System::redirect($url);
    }
    catch (\Throwable $e) {
      Civi::log()->error('EditRule error', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
      CRM_Core_Session::setStatus(ts('Error: %1', [1 => $e->getMessage()]), '', 'error');
      throw $e;
    }
  }

  /**
   * @return array<int,string>
   */
  protected function csvToArray(string $csv): array {
    if ($csv === '') {
      return [];
    }
    $parts = preg_split('/\s*,\s*/', trim($csv));
    $parts = $parts === FALSE ? [] : $parts;
    $out = [];
    foreach ($parts as $p) {
      if ($p !== '') {
        $out[] = $p;
      }
    }
    return $out;
  }

  protected function normalizeValue(string $raw): string {
    $s = trim($raw);
    if ($s === '') {
      return '[]';
    }
    if (preg_match('/^\s*\[.*\]\s*$/', $s) === 1) {
      return $s;
    }
    $ints = [];
    $split = preg_split('/\s*,\s*/', $s);
    $split = $split === FALSE ? [] : $split;
    foreach ($split as $p) {
      $n = $this->toInt($p);
      if ($n > 0) {
        $ints[] = $n;
      }
    }
    return '[' . implode(',', $ints) . ']';
  }

  // phpcs:disable Drupal.Commenting.FunctionComment.InvalidTypeHint

  /**
   * @param mixed $value
   */
  private function toInt(mixed $value): int {
    if (is_int($value)) {
      return $value;
    }
    if (is_float($value)) {
      return (int) $value;
    }
    if (is_string($value)) {
      $value = trim($value);
      if ($value === '') {
        return 0;
      }
      if (preg_match('/^-?\d+$/', $value) === 1) {
        return (int) $value;
      }
    }
    return 0;
  }

  /**
   * @param mixed $value
   */
  private function toBool(mixed $value): bool {
    if (is_bool($value)) {
      return $value;
    }
    if (is_int($value)) {
      return $value !== 0;
    }
    if (is_string($value)) {
      $v = strtolower(trim($value));
      return $v === '1' || $v === 'true' || $v === 'yes' || $v === 'on';
    }
    return FALSE;
  }

  /**
   * @param mixed $value
   */
  private function toString(mixed $value): string {
    if (is_string($value)) {
      return $value;
    }
    if (is_int($value) || is_float($value)) {
      return (string) $value;
    }
    return '';
  }

  /**
   * @param mixed $value
   * @return array<int,int>
   */
  private function toIntArray(mixed $value): array {
    if (is_array($value)) {
      return array_values(array_map([$this, 'toInt'], $value));
    }
    if (is_string($value)) {
      $parts = preg_split('/\s*,\s*/', $value);
      $parts = $parts === FALSE ? [] : $parts;
      return array_values(array_map([$this, 'toInt'], $parts));
    }
    return [];
  }

  /**
   * @param mixed $value
   * @return array<int,string>
   */
  private function toStringArray(mixed $value): array {
    if (is_array($value)) {
      return array_values(array_map([$this, 'toString'], $value));
    }
    if (is_string($value)) {
      $parts = preg_split('/\s*,\s*/', $value);
      $parts = $parts === FALSE ? [] : $parts;
      return array_values(array_map([$this, 'toString'], $parts));
    }
    return [];
  }

  // phpcs:disable Drupal.Commenting.FunctionComment.InvalidTypeHint

  /**
   * @param mixed $result
   * @return array<string,mixed>
   */
  private function firstRow(mixed $result): array {
    // phpcs: enable
    if ($result === NULL) {
      return [];
    }
    if (is_array($result)) {
      foreach ($result as $row) {
        return is_array($row) ? $row : (array) $row;
      }
      return [];
    }
    if ($result instanceof \Traversable) {
      foreach ($result as $row) {
        return is_array($row) ? $row : (array) $row;
      }
      return [];
    }
    if ($result instanceof \ArrayAccess && isset($result[0])) {
      $row = $result[0];
      return is_array($row) ? $row : (array) $row;
    }
    return [];
  }

  /**
   * @return array<string,string>
   */
  private function loadEntityFieldsSafe(object $wizard, string $entity): array {
    if (method_exists($wizard, 'loadEntityFields')) {
      $opts = $wizard->loadEntityFields($entity);
      return is_array($opts) ? $opts : [];
    }
    return [];
  }

  /**
   * @return array<string,string>
   */
  private function loadFieldOptionsSafe(object $wizard, string $entity, string $fieldName = ''): array {
    if (method_exists($wizard, 'loadFieldOptions')) {
      $opts = $wizard->loadFieldOptions($entity, $fieldName);
      return is_array($opts) ? $opts : [];
    }
    return [];
  }

}
