<?php
declare(strict_types = 1);

class CRM_Notification_Form_EntityRuleWizard extends CRM_Core_Form {

  private const NEW_RULESET_VALUE = 'new';

  protected string $entityType = 'Activity';
  /**
   * @var array<string,mixed> */
  protected array $editDefaults = [];

  public function preProcess(): void {
    $this->entityType = (string) CRM_Utils_Request::retrieve('entity_type', 'String', $this, FALSE, 'Activity');

    $ruleId = (int) CRM_Utils_Request::retrieve('rule_id', 'Positive', $this, FALSE, 0);
    if ($ruleId > 0) {
      $this->editDefaults = $this->loadRuleDefaults($ruleId);
      if (!empty($this->editDefaults['entity_type'])) {
        $this->entityType = (string) $this->editDefaults['entity_type'];
      }
    }

    $res = CRM_Core_Resources::singleton();
    $res->addScriptFile('notification', 'js/entity-rule-wizard.js', CRM_Core_Resources::DEFAULT_WEIGHT, 'html-header');
    $res->addStyleFile('notification', 'css/entity-rule-wizard.css', CRM_Core_Resources::DEFAULT_WEIGHT, 'html-header');

    $res->addSetting([
      'notification' => [
        'entityRuleWizard' => [
          'entity'          => $this->entityType,
          'newRulesetValue' => self::NEW_RULESET_VALUE,
        ],
      ],
    ]);
  }

  public function buildQuickForm(): void {
    $defaults = $this->defaultsForEntity($this->entityType);
    if (!empty($this->editDefaults)) {
      $defaults = array_merge($defaults, $this->editDefaults);
    }

    $this->add('hidden', 'rule_id');

    $this->add('text', 'entity_type', ts('Entity Type'), [], TRUE);
    $this->setDefaults(['entity_type' => $this->entityType]);
    $this->getElement('entity_type')->freeze();

    $rulesetChoices = ['' => ts('- none -')]
      + $this->getRuleSetOptions($this->entityType)
      + [self::NEW_RULESET_VALUE => ts('— New Rule Set…')];
    $this->add('select', 'ruleset_id', ts('RuleSet'),
      $rulesetChoices, FALSE, ['class' => 'crm-select2', 'id' => 'ruleset_id']);
    $this->add('text', 'ruleset_title', ts('RuleSet Title'));
    $this->add('text', 'rule_title', ts('Rule Title'), [], TRUE);

    $this->addEntityRef('contact_ids_er', ts('Contacts'), [
      'entity' => 'Contact',
      'api' => ['check_permissions' => 1],
      'select' => ['minimumInputLength' => 1, 'multiple' => TRUE, 'allowClear' => TRUE],
    ]);
    $this->addEntityRef('group_ids_er', ts('Groups'), [
      'entity' => 'Group',
      'api' => ['is_active' => 1],
      'select' => ['minimumInputLength' => 0, 'multiple' => TRUE, 'allowClear' => TRUE],
    ]);

    $langOpts = $this->getLanguageOptions();
    $this->add('select', 'languages', ts('Languages'), $langOpts, TRUE, [
      'class' => 'crm-select2',
      'multiple' => TRUE,
      'id' => 'languages_select',
    ]);

    $fieldOpts = $this->getEntityFieldOptions($this->entityType);
    $this->add('select', 'field_name',
      ts('Field Name') . ' ' . ts('⭐star indicates field has option values'),
      $fieldOpts, TRUE, ['class' => 'crm-select2', 'id' => 'field_name']);

    $ops = ['in' => 'in', 'not_in' => 'not_in', 'eq' => 'eq', 'neq' => 'neq'];
    $this->add('select', 'operator_before',
      ts('Operator Before'), $ops, TRUE, ['class' => 'crm-select2', 'id' => 'operator_before']);
    $this->add('text', 'value_before', ts('or raw (Before)'), ['id' => 'value_before']);

    $this->add('select', 'operator_after',
      ts('Operator After'), $ops, TRUE, ['class' => 'crm-select2', 'id' => 'operator_after']);
    $this->add('text', 'value_after', ts('or raw (After)'), ['id' => 'value_after']);

    $prefillBefore = $this->decodeJsonArray((string) ($defaults['value_before'] ?? '[]'));
    $prefillAfter  = $this->decodeJsonArray((string) ($defaults['value_after'] ?? '[]'));
    $this->add('select', 'value_before_opts', ts('Value Before (by label)'), [], FALSE,
      [
        'class' => 'crm-select2',
        'multiple' => TRUE,
        'id'
        => 'value_before_opts',
        'data-prefill' => json_encode($prefillBefore),
      ]);
    $this->add('select', 'value_after_opts', ts('Value After (by label)'), [], FALSE,
      [
        'class' => 'crm-select2',
        'multiple' => TRUE,
        'id'
        => 'value_after_opts',
        'data-prefill' => json_encode($prefillAfter),
      ]);

    $mtDefault = isset($defaults['message_template_id']) ? (string) $defaults['message_template_id'] : '';
    $this->add('select', 'message_template_id', ts('Message Template'),
      ['' => ts('- select Message Template -')], TRUE,
      ['class' => 'crm-select2', 'id' => 'message_template_id', 'data-default' => $mtDefault]
    );

    $this->setDefaults($defaults);

    $this->addButtons([
      ['type' => 'next', 'name' => ts('Create / Update'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => ts('Cancel')],
    ]);
    $this->addFormRule([$this, 'formRule']);

    parent::buildQuickForm();
  }

  /**
   * @return TRUE|array<string,string> */
  public function formRule($values) {
    $errors = [];
    $rsSel = (string) ($values['ruleset_id'] ?? '');
    $rsTitle = trim((string) ($values['ruleset_title'] ?? ''));
    if ($rsSel === '' || $rsSel === self::NEW_RULESET_VALUE) {
      if ($rsTitle === '') {
        $errors['ruleset_title'] = ts('Please provide a RuleSet Title when not selecting an existing RuleSet.');
      }
    }

    $mtId = (int) ($values['message_template_id'] ?? 0);
    if (!$mtId) {
      $mtId = (int) CRM_Utils_Request::retrieve('message_template_id', 'Positive', $this, FALSE, 0);
    }
    if (!$mtId) {
      $errors['message_template_id'] = ts('Select a Message Template.');
    }

    return $errors ?: TRUE;
  }

  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function postProcess(): void {
    // phpcs:enable
    $v = $this->exportValues();

    $entity    = (string) $v['entity_type'];
    $ruleId    = (int) ($v['rule_id'] ?? 0);

    $rsRaw     = (string) ($v['ruleset_id'] ?? '');
    $rulesetId = ctype_digit($rsRaw) ? (int) $rsRaw : 0;
    if (!($rsRaw !== '' && $rsRaw !== self::NEW_RULESET_VALUE && $rulesetId > 0)) {
      $rsTitle   = trim((string) ($v['ruleset_title'] ?? ''))
        ?: $this->defaultsForEntity($entity)['ruleset_title'];
      $rulesetId = $this->ensureRuleSet($rsTitle, $entity);
    }

    $ruTitle = trim((string) $v['rule_title']) ?: $this->defaultsForEntity($entity)['rule_title'];

    $langs = $this->normalizeToArray($v['languages'] ?? [])
      ?: $this->csvToArray($this->defaultsForEntity($entity)['languages_csv']);

    $contacts = $this->entityRefIdsToIntArray($v['contact_ids_er'] ?? []);
    $groups   = $this->entityRefIdsToIntArray($v['group_ids_er'] ?? []);
    $ctypes   = [];

    $field     = trim((string) $v['field_name']);
    $opBefore  = (string) $v['operator_before'];
    $opAfter   = (string) $v['operator_after'];
    $valBefore = $this->valuesFromEither($v, 'value_before_opts', 'value_before');
    $valAfter  = $this->valuesFromEither($v, 'value_after_opts', 'value_after');

    $mtId = 0;
    if (isset($v['message_template_id'])) {
      $mtId = (int) (is_array($v['message_template_id']) ?
        reset($v['message_template_id']) : $v['message_template_id']);
    }
    if (!$mtId) {
      $mtId = (int) CRM_Utils_Request::retrieve('message_template_id', 'Positive', $this, FALSE, 0);
      if (!$mtId && isset($_POST['message_template_id'])) {
        $mtId = (int) $_POST['message_template_id'];
      }
    }

    $this->ensureQueue();
    $ruId = $this->ensureRule($rulesetId, $ruTitle, $langs, $ruleId);

    $this->resetContactSelection($ruId);
    $this->createContactSelection($ruId, $contacts, $groups, $ctypes);

    $this->ensureFieldMonitoring($ruId, $field, $opBefore, $valBefore, $opAfter, $valAfter);
    if ($mtId > 0) {
      $this->replaceRuleMessageTemplate($ruId, $mtId, $langs);
    }

    CRM_Core_Session::setStatus(ts('Rule created/updated'), '', 'success');
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/notification/entities', 'reset=1'));
  }

  /**
   * @return array<string,mixed> */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  protected function loadRuleDefaults(int $ruleId): array {
    // phpcs:enable
    $out = ['rule_id' => $ruleId];

    try {

      $r = \Civi\Api4\NotificationRule::get()
        ->addWhere('id', '=', $ruleId)
        ->addSelect('id', 'rule_set_id', 'title', 'languages')
        ->setLimit(1)->execute();
      if (!$r->count()) {
        return $out;
      }

      $rule = (array) $r[0];
      $out['ruleset_id'] = (int) $rule['rule_set_id'];
      $out['rule_title'] = (string) $rule['title'];

      $langs = $rule['languages'] ?? [];
      if (is_string($langs)) {
        $langs = json_decode($langs, TRUE) ?: [];
      }
      $out['languages'] = $this->normalizeToArray($langs);

      $rs = \Civi\Api4\NotificationRuleSet::get()
        ->addWhere('id', '=', (int) $rule['rule_set_id'])
        ->addSelect('title', 'monitored_entity_type')
        ->setLimit(1)->execute();
      if ($rs->count()) {
        $out['ruleset_title'] = (string) $rs[0]['title'];
        $out['entity_type']   = (string) $rs[0]['monitored_entity_type'];
      }

      $sel = \Civi\Api4\NotificationContactSelection::get()
        ->addWhere('rule_id', '=', $ruleId)
        ->addSelect('contact_ids', 'group_ids', 'contact_type_ids')
        ->setLimit(1)->execute();
      if ($sel->count()) {
        $out['contact_ids_er'] = $this->normalizeToArray($sel[0]['contact_ids'] ?? []);
        $out['group_ids_er']   = $this->normalizeToArray($sel[0]['group_ids'] ?? []);
      }

      $fm = \Civi\Api4\NotificationFieldMonitoring::get()
        ->addWhere('rule_id', '=', $ruleId)
        ->addSelect('field_name', 'operator_before', 'value_before', 'operator_after', 'value_after')
        ->setLimit(1)->execute();
      if ($fm->count()) {
        $out['field_name']      = (string) $fm[0]['field_name'];
        $out['operator_before'] = (string) $fm[0]['operator_before'];
        $out['value_before']    = (string) $fm[0]['value_before'];
        $out['operator_after']  = (string) $fm[0]['operator_after'];
        $out['value_after']     = (string) $fm[0]['value_after'];
        $out['value_before_opts'] = $this->decodeJsonArray((string) $fm[0]['value_before']);
        $out['value_after_opts']  = $this->decodeJsonArray((string) $fm[0]['value_after']);
      }

      $mt = \Civi\Api4\NotificationRuleMessageTemplate::get()
        ->addWhere('rule_id', '=', $ruleId)
        ->addSelect('msg_template_id', 'languages')
        ->addOrderBy('id', 'DESC')
        ->setLimit(1)->execute();
      if ($mt->count()) {
        $out['message_template_id'] = (int) $mt[0]['msg_template_id'];
        if (empty($out['languages'])) {
          $langs2 = $mt[0]['languages'] ?? [];
          if (is_string($langs2)) {
            $langs2 = json_decode($langs2, TRUE) ?: [];
          }
          $out['languages'] = $this->normalizeToArray($langs2);
        }
      }
    }
    catch (\Throwable $e) {

    }

    return $out;
  }

  protected function getRuleSetOptions(string $entity): array {
    try {
      $rs = \Civi\Api4\NotificationRuleSet::get()
        ->addWhere('monitored_entity_type', '=', $entity)
        ->addSelect('id', 'title', 'is_active')
        ->addOrderBy('title', 'ASC')->execute();
      $out = [];
      foreach ($rs as $row) {
        $label = $row['title'] . (!empty($row['is_active']) ? '' : ' ' . ts('(inactive)'));
        $out[(int) $row['id']] = $label;
      }
      return $out;
    }
    catch (\Throwable $e) {
      return [];
    }
  }

  protected function getLanguageOptions(): array {
    $langs = CRM_Core_I18n::languages();
    $out = [];
    foreach ($langs as $code => $label) {
      $out[$code] = $label;
    }
    return $out ?: ['en_US' => 'English (US)'];
  }

  protected function getEntityFieldOptions(string $entity): array {
    $fields = $this->getFieldsForEntity($entity);
    $opts = [];
    foreach ($fields as $f) {
      if (!empty($f['readonly'])) {
        continue;
      }
      $name = (string) $f['name'];
      $label = ($f['label'] ?? $name);
      $hasOptions = !empty($f['options']) || !empty($f['optionGroup']) || !empty($f['pseudoconstant']);
      $opts[$name] = $label . ($hasOptions ? ' ⭐' : '');
    }
    asort($opts, SORT_NATURAL | SORT_FLAG_CASE);
    return $opts;
  }

  protected function getFieldsForEntity(string $entity): array {
    try {
      $class = "\\Civi\\Api4\\$entity";
      if ($entity === 'Case' && !class_exists($class)) {
        $class = '\\Civi\\Api4\\CaseEntity';
      }
      if (class_exists($class) && method_exists($class, 'getFields')) {
        $res = $class::getFields(FALSE)->execute();
        return iterator_to_array($res);
      }
    }
    catch (\Throwable $e) {
    }
    return [];
  }

  protected function valuesFromEither(array $v, string $selectKey, string $rawKey): string {
    if (!empty($v[$selectKey])) {
      $vals = array_values(array_filter((array) $v[$selectKey], static function ($x) {
        return $x !== '' && $x !== NULL;
      }));
      $vals = array_map(static function ($x) {
        return (is_string($x) && is_numeric($x)) ? 0 + $x : $x;
      }, $vals);
      $json = json_encode($vals, JSON_UNESCAPED_UNICODE);
      return $json !== FALSE ? $json : '[]';
    }
    return $this->normalizeValue($v[$rawKey] ?? '');
  }

  protected function defaultsForEntity(string $entity): array {
    $rs    = $entity . ' – Notifications';
    $ru    = $entity . ' → Completed → notify';
    $langs = ['es_ES'];
    $field = 'status_id';
    $opsB  = 'not_in';
    $opsA  = 'in';
    $valB  = '';
    $valA  = '';
    if ($entity === 'Activity') {
      $completed = $this->optionValue('activity_status', 'Completed');
      if ($completed) {
        $valB = "[$completed]";
        $valA = "[$completed]";
      }
    }

    return [
      'ruleset_title'     => $rs,
      'rule_title'        => $ru,
      'languages'         => $langs,
      'field_name'        => $field,
      'operator_before'   => $opsB,
      'operator_after'    => $opsA,
      'value_before'      => $valB,
      'value_after'       => $valA,
    ];
  }

  protected function csvToArray(?string $csv): array {
    if (!$csv) {
      return [];
    }
    $parts = preg_split('/\s*,\s*/', trim($csv));
    return array_values(array_filter($parts, fn($p) => $p !== ''));
  }

  protected function normalizeValue(?string $raw): string {
    $s = trim((string) $raw);
    if ($s === '') {
      return '[]';
    }
    if (preg_match('/^\s*\[.*\]\s*$/', $s)) {
      return $s;
    }
    $parts = preg_split('/\s*,\s*/', $s);
    $vals = [];
    foreach ($parts as $p) {
      if ($p === '' || $p === NULL) {
        continue;
      }
      if (is_numeric($p)) {
        $vals[] = 0 + $p;
      }
      else {
        $vals[] = $p;
      }
    }
    $json = json_encode($vals, JSON_UNESCAPED_UNICODE);
    return $json !== FALSE ? $json : '[]';
  }

  protected function decodeJsonArray(string $json): array {
    $a = json_decode($json, TRUE);
    return is_array($a) ? $a : [];
  }

  protected function normalizeToArray($v): array {
    if (is_array($v)) {
      return array_values($v);
    }
    if (is_string($v)) {
      $s = trim($v);
      if ($s === '') {
        return [];
      }
      if ($s[0] === '[') {
        $a = json_decode($s, TRUE);
        return is_array($a) ? array_values($a) : [$s];
      }
      return array_values(array_filter(preg_split('/\s*,\s*/', $s)));
    }
    return [];
  }

  protected function entityRefIdsToIntArray($erValue): array {
    if (is_string($erValue)) {
      $parts = array_values(array_filter(preg_split('/\s*,\s*/', $erValue)));
      return array_values(array_filter(array_map('intval', $parts)));
    }
    $out = [];
    foreach ((array) $erValue as $item) {
      if (is_array($item) && isset($item['id'])) {
        $out[] = (int) $item['id'];
      }
      else {
        $out[] = (int) $item;
      }
    }
    return array_values(array_filter($out));
  }

  protected function optionValue(string $groupName, string $name): int {
    try {
      $r = \Civi\Api4\OptionValue::get()
        ->addWhere('option_group_id:name', '=', $groupName)
        ->addWhere('name', '=', $name)
        ->addSelect('value')->setLimit(1)->execute();
      if (isset($r[0]['value'])) {
        return (int) $r[0]['value'];
      }
    }
    catch (\Throwable $e) {
    }
    return 0;
  }

  protected function ensureQueue(): void {
    CRM_Core_DAO::executeQuery(
      "INSERT IGNORE INTO civicrm_queue (name,type,status,is_template) VALUES (%1,'Sql','active',0)",
      [1 => ['notification.jobs', 'String']]
    );
  }

  protected function ensureRule(int $ruleSetId, string $title, array $languages, int $ruleId = 0): int {
    if ($ruleId > 0) {
      \Civi\Api4\NotificationRule::update()
        ->addWhere('id', '=', $ruleId)
        ->addValue('rule_set_id', $ruleSetId)
        ->addValue('title', $title)
        ->addValue('is_active', TRUE)
        ->addValue('on_create', FALSE)
        ->addValue('on_update', TRUE)
        ->addValue('on_delete', FALSE)
        ->addValue('languages', $languages)->execute();
      return $ruleId;
    }

    $existing = \Civi\Api4\NotificationRule::get()
      ->addWhere('rule_set_id', '=', $ruleSetId)
      ->addWhere('title', '=', $title)
      ->addSelect('id')->setLimit(1)->execute();
    if ($existing->count()) {
      \Civi\Api4\NotificationRule::update()
        ->addWhere('id', '=', $existing[0]['id'])
        ->addValue('rule_set_id', $ruleSetId)
        ->addValue('title', $title)
        ->addValue('is_active', TRUE)
        ->addValue('on_create', FALSE)
        ->addValue('on_update', TRUE)
        ->addValue('on_delete', FALSE)
        ->addValue('languages', $languages)->execute();
      return (int) $existing[0]['id'];
    }
    $create = \Civi\Api4\NotificationRule::create()
      ->addValue('rule_set_id', $ruleSetId)
      ->addValue('title', $title)
      ->addValue('is_active', TRUE)
      ->addValue('on_create', FALSE)
      ->addValue('on_update', TRUE)
      ->addValue('on_delete', FALSE)
      ->addValue('languages', $languages)->execute();
    return (int) $create[0]['id'];
  }

  protected function ensureRuleSet(string $title, string $entity): int {
    $existing = \Civi\Api4\NotificationRuleSet::get()
      ->addWhere('title', '=', $title)
      ->addWhere('monitored_entity_type', '=', $entity)
      ->addSelect('id')->setLimit(1)->execute();
    if ($existing->count()) {
      \Civi\Api4\NotificationRuleSet::update()
        ->addWhere('id', '=', $existing[0]['id'])
        ->addValue('is_active', TRUE)
        ->addValue('is_execute_only_first_rule', FALSE)->execute();
      return (int) $existing[0]['id'];
    }
    $create = \Civi\Api4\NotificationRuleSet::create()
      ->addValue('title', $title)
      ->addValue('monitored_entity_type', $entity)
      ->addValue('source_entity_type', $entity)
      ->addValue('source_entity_id', 0)
      ->addValue('is_active', TRUE)
      ->addValue('is_execute_only_first_rule', FALSE)->execute();
    return (int) $create[0]['id'];
  }

  protected function resetContactSelection(int $ruleId): void {
    \Civi\Api4\NotificationContactSelection::delete()
      ->addWhere('rule_id', '=', $ruleId)->execute();
  }

  protected function createContactSelection(int $ruleId, array $contactIds, array $groupIds, array $contactTypes)
  : void {
    \Civi\Api4\NotificationContactSelection::create()
      ->addValue('rule_id', $ruleId)
      ->addValue('contact_ids', $contactIds)
      ->addValue('group_ids', $groupIds)
      ->addValue('contact_type_ids', $contactTypes)->execute();
  }

  protected function ensureFieldMonitoring(
    int $ruleId,
    string $field,
    string $opBefore,
    string $valBefore,
    string $opAfter,
    string $valAfter
  ): void {
    $existing = \Civi\Api4\NotificationFieldMonitoring::get()
      ->addWhere('rule_id', '=', $ruleId)
      ->addWhere('field_name', '=', $field)
      ->addSelect('id')->setLimit(1)->execute();
    if ($existing->count()) {
      \Civi\Api4\NotificationFieldMonitoring::update()
        ->addWhere('id', '=', $existing[0]['id'])
        ->addValue('operator_before', $opBefore)
        ->addValue('value_before', $valBefore)
        ->addValue('operator_after', $opAfter)
        ->addValue('value_after', $valAfter)->execute();
    }
    else {
      \Civi\Api4\NotificationFieldMonitoring::create()
        ->addValue('rule_id', $ruleId)
        ->addValue('field_name', $field)
        ->addValue('operator_before', $opBefore)
        ->addValue('value_before', $valBefore)
        ->addValue('operator_after', $opAfter)
        ->addValue('value_after', $valAfter)->execute();
    }
  }

  /**
   */
  protected function replaceRuleMessageTemplate(int $ruleId, int $mtId, array $languages): void {
    \Civi\Api4\NotificationRuleMessageTemplate::delete()
      ->addWhere('rule_id', '=', $ruleId)
      ->execute();

    \Civi\Api4\NotificationRuleMessageTemplate::create()
      ->addValue('rule_id', $ruleId)
      ->addValue('msg_template_id', $mtId)
      ->addValue('languages', $languages)
      ->execute();
  }

  protected function entityToken(string $entity): string {
    $m = [
      'Activity' => 'activity',
      'Contribution' => 'contribution',
      'Membership' => 'membership',
      'Participant' => 'participant',
      'Case' => 'case',
    ];
    return $m[$entity] ?? strtolower($entity);
  }

}
