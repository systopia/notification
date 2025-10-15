<?php
declare(strict_types = 1);

/**
 * @method void setDefaults(array $defaults)
 * @method mixed getElement(string $name)
 * @method array exportValues()
 * @method void addFormRule(callable $callback)
 * @method string getButtonName(string $type = 'next', ?string $name = null)
 */
class CRM_Notification_Form_EntityRuleWizard extends CRM_Core_Form {

  private const NEW_RULESET_VALUE = 'new';

  protected string $entityType = 'Activity';

  /**
   * @var array<string,mixed> */
  protected array $editDefaults = [];

  public function preProcess(): void {
    $this->entityType = $this->toString(CRM_Utils_Request::retrieve('entity_type', 'String', $this, FALSE, 'Activity'));

    $ruleId = $this->toInt(CRM_Utils_Request::retrieve('rule_id', 'Positive', $this, FALSE, 0));
    if ($ruleId > 0) {
      $this->editDefaults = $this->loadRuleDefaults($ruleId);
      $edEntity = $this->toString($this->editDefaults['entity_type'] ?? '');
      if ($edEntity !== '') {
        $this->entityType = $edEntity;
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

// phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  public function buildQuickForm(): void {
    // phpcs:enable
    $defaults = $this->defaultsForEntity($this->entityType);
    if ($this->editDefaults !== []) {
      $defaults = array_merge($defaults, $this->editDefaults);
    }

    $this->add('hidden', 'rule_id');

    $this->add('text', 'entity_type', ts('Entity Type'), [], TRUE);
    $this->setDefaults(['entity_type' => $this->entityType]);
    $et = $this->getElement('entity_type');
    if (is_object($et)) {
      if (method_exists($et, 'freeze')) {
        $et->freeze();
      }
    }

    $rulesetChoices = ['' => ts('- none -')]
      + $this->getRuleSetOptions($this->entityType)
      + [self::NEW_RULESET_VALUE => ts('— New Rule Set…')];

    $this->add('select', 'ruleset_id', ts('RuleSet'),
      $rulesetChoices, FALSE, ['class' => 'crm-select2', 'id' => 'ruleset_id']);

    $this->add('text', 'ruleset_title', ts('RuleSet Title'));
    $this->add('text', 'rule_title', ts('Rule Title'), [], TRUE);

    $this->add('advcheckbox', 'is_active', ts('Active'));

    // Recipients
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
    $this->add('select', 'operator_before', ts('Operator Before'), $ops, TRUE,
      ['class' => 'crm-select2', 'id' => 'operator_before']);
    $this->add('text', 'value_before', ts('or raw (Before)'), ['id' => 'value_before']);

    $this->add('select', 'operator_after', ts('Operator After'), $ops, TRUE,
      ['class' => 'crm-select2', 'id' => 'operator_after']);
    $this->add('text', 'value_after', ts('or raw (After)'), ['id' => 'value_after']);

    $prefillBefore = $this->decodeJsonArray($this->toString($defaults['value_before'] ?? '[]'));
    $prefillAfter  = $this->decodeJsonArray($this->toString($defaults['value_after'] ?? '[]'));
    $this->add('select', 'value_before_opts', ts('Value Before (by label)'), [], FALSE,
      [
        'class' => 'crm-select2',
        'multiple' => TRUE,
        'id' => 'value_before_opts',
        'data-prefill' => json_encode($prefillBefore),
      ]);
    $this->add('select', 'value_after_opts', ts('Value After (by label)'), [], FALSE,
      [
        'class' => 'crm-select2',
        'multiple' => TRUE,
        'id' => 'value_after_opts',
        'data-prefill' => json_encode($prefillAfter),
      ]);

    // Message
    $mtDefault = isset($defaults['message_template_id']) ? $this->toString($defaults['message_template_id']) : '';
    $this->add('select', 'message_template_id', ts('Message Template'),
      ['' => ts('- select Message Template -')], TRUE,
      ['class' => 'crm-select2', 'id' => 'message_template_id', 'data-default' => $mtDefault]
    );

    // Defaults
    $defaults += [
      'is_active' => isset($defaults['is_active']) ? (int) (bool) $defaults['is_active'] : 1,
      'rule_id'   => $this->toInt($this->editDefaults['rule_id'] ?? 0),
    ];
    $this->setDefaults($defaults);

    $buttons = [
      ['type' => 'next', 'name' => ts('Create / Update'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => ts('Cancel')],
    ];
    $hasRule = $this->toInt($this->editDefaults['rule_id'] ?? 0) > 0;
    if ($hasRule) {
      $buttons[] = [
        'type'    => 'next',
        'name'    => ts('Delete rule'),
        'subName' => 'delete',
        'class'   => 'crm-button crm-button-type-delete',
      ];
    }
    $this->addButtons($buttons);

    $this->addFormRule([$this, 'formRule']);
    parent::buildQuickForm();
  }

  /**
   * @param array<string,mixed> $values
   * @return true|array<string,string>
   */
  public function formRule(array $values) {
    if ($this->controller->getButtonName() === $this->getButtonName('next', 'delete')) {
      return TRUE;
    }

    $errors = [];
    $rsSel = $this->toString($values['ruleset_id'] ?? '');
    $rsTitle = trim($this->toString($values['ruleset_title'] ?? ''));
    if ($rsSel === '' || $rsSel === self::NEW_RULESET_VALUE) {
      if ($rsTitle === '') {
        $errors['ruleset_title'] = ts('Please provide a RuleSet Title when not selecting an existing RuleSet.');
      }
    }

    $mtId = $this->toInt($values['message_template_id'] ?? 0);
    if ($mtId <= 0) {
      $mtId = $this->toInt(CRM_Utils_Request::retrieve('message_template_id', 'Positive', $this, FALSE, 0));
    }
    if ($mtId <= 0) {
      $errors['message_template_id'] = ts('Select a Message Template.');
    }

    return $errors !== [] ? $errors : TRUE;
  }

  /**
   *
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded
  public function postProcess(): void {
    // phpcs:enable
    $v = $this->exportValues();

    $ruleId = $this->toInt($v['rule_id'] ?? 0);

    if ($this->controller->getButtonName() === $this->getButtonName('next', 'delete') && $ruleId > 0) {
      \Civi\Api4\NotificationContactSelection::delete()->addWhere('rule_id', '=', $ruleId)->execute();
      \Civi\Api4\NotificationFieldMonitoring::delete()->addWhere('rule_id', '=', $ruleId)->execute();
      \Civi\Api4\NotificationRuleMessageTemplate::delete()->addWhere('rule_id', '=', $ruleId)->execute();
      \Civi\Api4\NotificationRule::delete()->addWhere('id', '=', $ruleId)->execute();

      CRM_Core_Session::setStatus(ts('Rule deleted'), '', 'success');
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/notification/entities', 'reset=1'));
      return;
    }

    $entity = $this->toString($v['entity_type'] ?? '');

    $rsRaw     = $this->toString($v['ruleset_id'] ?? '');
    $rulesetId = ctype_digit($rsRaw) ? (int) $rsRaw : 0;
    if (!($rsRaw !== '' && $rsRaw !== self::NEW_RULESET_VALUE && $rulesetId > 0)) {
      $defaultsForEntity = $this->defaultsForEntity($entity);
      $rsTitle   = trim($this->toString($v['ruleset_title'] ?? ($defaultsForEntity['ruleset_title'] ?? '')));
      $rulesetId = $this->ensureRuleSet($rsTitle, $entity);
    }

    $defaultsForEntity = $this->defaultsForEntity($entity);
    $ruTitle = trim($this->toString($v['rule_title'] ?? ($defaultsForEntity['rule_title'] ?? '')));

    // ensure languages are array<int,string>
    $langs = $this->normalizeToStringArray($v['languages'] ?? []);
    if ($langs === []) {
      $langs = $this->csvToArray($this->toString($defaultsForEntity['languages_csv'] ?? ''));
    }

    $contacts = $this->entityRefIdsToIntArray($v['contact_ids_er'] ?? []);
    $groups   = $this->entityRefIdsToIntArray($v['group_ids_er'] ?? []);
    $ctypes   = [];

    $field     = trim($this->toString($v['field_name'] ?? ''));
    $opBefore  = $this->toString($v['operator_before'] ?? '');
    $opAfter   = $this->toString($v['operator_after'] ?? '');
    $valBefore = $this->valuesFromEither($v, 'value_before_opts', 'value_before');
    $valAfter  = $this->valuesFromEither($v, 'value_after_opts', 'value_after');

    $mtId = 0;
    if (isset($v['message_template_id'])) {
      $mtId = is_array($v['message_template_id'])
        ? $this->toInt(reset($v['message_template_id']))
        : $this->toInt($v['message_template_id']);
    }
    if ($mtId <= 0) {
      $mtId = $this->toInt(CRM_Utils_Request::retrieve('message_template_id', 'Positive', $this, FALSE, 0));
    }

    $isActive = (bool) ($v['is_active'] ?? FALSE);

    $this->ensureQueue();
    $ruId = $this->ensureRule($rulesetId, $ruTitle, $langs, $ruleId, $isActive);

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
   * @return array<string,mixed>
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded
  protected function loadRuleDefaults(int $ruleId): array {
    // phpcs:enable
    $out = ['rule_id' => $ruleId, 'languages' => []];

    try {
      $r = \Civi\Api4\NotificationRule::get()
        ->addWhere('id', '=', $ruleId)
        ->addSelect('id', 'rule_set_id', 'title', 'languages', 'is_active')
        ->setLimit(1)->execute();
      if ($r->count() <= 0) {
        return $out;
      }

      $rule = $this->firstRow($r);
      $out['ruleset_id'] = $this->toInt($rule['rule_set_id'] ?? 0);
      $out['rule_title'] = $this->toString($rule['title'] ?? '');
      $out['is_active']  = (bool) ($rule['is_active'] ?? FALSE);

      $langs = $rule['languages'] ?? [];
      if (is_string($langs)) {
        $tmp = json_decode($langs, TRUE);
        $langs = is_array($tmp) ? $tmp : [];
      }
      $out['languages'] = $this->normalizeToStringArray($langs);

      $rs = \Civi\Api4\NotificationRuleSet::get()
        ->addWhere('id', '=', $this->toInt($rule['rule_set_id'] ?? 0))
        ->addSelect('title', 'monitored_entity_type')
        ->setLimit(1)->execute();
      if ($rs->count() > 0) {
        $rsRow = $this->firstRow($rs);
        $out['ruleset_title'] = $this->toString($rsRow['title'] ?? '');
        $out['entity_type']   = $this->toString($rsRow['monitored_entity_type'] ?? '');
      }

      $sel = \Civi\Api4\NotificationContactSelection::get()
        ->addWhere('rule_id', '=', $ruleId)
        ->addSelect('contact_ids', 'group_ids', 'contact_type_ids')
        ->setLimit(1)->execute();
      if ($sel->count() > 0) {
        $selRow = $this->firstRow($sel);
        $out['contact_ids_er'] = $this->normalizeToArray($selRow['contact_ids'] ?? []);
        $out['group_ids_er']   = $this->normalizeToArray($selRow['group_ids'] ?? []);
      }

      $fm = \Civi\Api4\NotificationFieldMonitoring::get()
        ->addWhere('rule_id', '=', $ruleId)
        ->addSelect('field_name', 'operator_before', 'value_before', 'operator_after', 'value_after')
        ->setLimit(1)->execute();
      if ($fm->count() > 0) {
        $fmRow = $this->firstRow($fm);
        $out['field_name']        = $this->toString($fmRow['field_name'] ?? '');
        $out['operator_before']   = $this->toString($fmRow['operator_before'] ?? '');
        $out['value_before']      = $this->toString($fmRow['value_before'] ?? '');
        $out['operator_after']    = $this->toString($fmRow['operator_after'] ?? '');
        $out['value_after']       = $this->toString($fmRow['value_after'] ?? '');
        $out['value_before_opts'] = $this->decodeJsonArray($this->toString($fmRow['value_before'] ?? ''));
        $out['value_after_opts']  = $this->decodeJsonArray($this->toString($fmRow['value_after'] ?? ''));
      }

      $mt = \Civi\Api4\NotificationRuleMessageTemplate::get()
        ->addWhere('rule_id', '=', $ruleId)
        ->addSelect('msg_template_id', 'languages')
        ->addOrderBy('id', 'DESC')
        ->setLimit(1)->execute();
      if ($mt->count() > 0) {
        $mtRow = $this->firstRow($mt);
        $out['message_template_id'] = $this->toInt($mtRow['msg_template_id'] ?? 0);
        if ($out['languages'] === []) {
          $langs2 = $mtRow['languages'] ?? [];
          if (is_string($langs2)) {
            $tmp2 = json_decode($langs2, TRUE);
            $langs2 = is_array($tmp2) ? $tmp2 : [];
          }
          $out['languages'] = $this->normalizeToStringArray($langs2);
        }
      }
    }
    catch (\Throwable $e) {
      Civi::log()->warning('loadRuleDefaults warning', ['msg' => $e->getMessage()]);
      throw $e;
    }

    return $out;
  }

  /**
   * @return array<int,string>
   */
  protected function getRuleSetOptions(string $entity): array {
    try {
      $rs = \Civi\Api4\NotificationRuleSet::get()
        ->addWhere('monitored_entity_type', '=', $entity)
        ->addSelect('id', 'title', 'is_active')
        ->addOrderBy('title', 'ASC')->execute();
      $out = [];
      foreach ($rs as $row) {
        $rowArr = (array) $row;
        $labelTitle = $this->toString($rowArr['title'] ?? '');
        $isActive = (bool) ($rowArr['is_active'] ?? FALSE);
        $inactiveSuffix = $isActive ? '' : ' ' . ts('(inactive)');
        $out[$this->toInt($rowArr['id'] ?? 0)] = $labelTitle . $inactiveSuffix;
      }
      return $out;
    }
    catch (\Throwable $e) {
      Civi::log()->warning('getRuleSetOptions warning', ['msg' => $e->getMessage()]);
      throw $e;
    }
  }

  /**
   * @return array<string,string>
   */
  protected function getLanguageOptions(): array {
    /** @var array<string,string> $langs */
    $langs = CRM_Core_I18n::languages();
    $out = [];
    foreach ($langs as $code => $label) {
      $out[$code] = $label;
    }
    return $out !== [] ? $out : ['en_US' => 'English (US)'];
  }

  /**
   * @return array<string,string>
   */
  protected function getEntityFieldOptions(string $entity): array {
    $fields = $this->getFieldsForEntity($entity);
    $opts = [];
    foreach ($fields as $f) {
      if (($f['readonly'] ?? FALSE) === TRUE) {
        continue;
      }
      $name = $this->toString($f['name'] ?? '');
      if ($name === '') {
        continue;
      }
      $label = $this->toString($f['label'] ?? $name);
      $hasOptions = (isset($f['options']) && $f['options'] !== [])
        || array_key_exists('optionGroup', $f)
        || array_key_exists('pseudoconstant', $f);
      $opts[$name] = $label . ($hasOptions ? ' ⭐' : '');
    }
    asort($opts, SORT_NATURAL | SORT_FLAG_CASE);
    return $opts;
  }

  /**
   * @return array<int,array<string,mixed>>
   */
  protected function getFieldsForEntity(string $entity): array {
    try {
      $class = "\\Civi\\Api4\\$entity";
      if ($entity === 'Case' && !class_exists($class)) {
        $class = '\\Civi\\Api4\\CaseEntity';
      }
      if (class_exists($class)) {
        if (method_exists($class, 'getFields')) {
          $res = $class::getFields(FALSE)->execute();
          /** @var array<int,array<string,mixed>> $arr */
          $arr = iterator_to_array($res);
          return $arr;
        }
      }
    }
    catch (\Throwable $e) {
      Civi::log()->warning('getFieldsForEntity warning', ['msg' => $e->getMessage()]);
      throw $e;
    }
    return [];
  }

  /**
   * @param array<string,mixed> $v
   */
  protected function valuesFromEither(array $v, string $selectKey, string $rawKey): string {
    $hasSelect = isset($v[$selectKey]) && $v[$selectKey] !== [] && $v[$selectKey] !== '';
    if ($hasSelect) {
      $vals = array_values(array_filter((array) $v[$selectKey], static function ($x): bool {
        return $x !== '';
      }));
      $vals = array_map(static function ($x) {
        return (is_string($x) && is_numeric($x)) ? 0 + $x : $x;
      }, $vals);
      $json = json_encode($vals, JSON_UNESCAPED_UNICODE);
      return $json !== FALSE ? $json : '[]';
    }
    return $this->normalizeValue($this->toString($v[$rawKey] ?? ''));
  }

  /**
   * @return array<string,mixed>
   */
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
      if ($completed > 0) {
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
      'languages_csv'     => implode(',', $langs),
    ];
  }

  /**
   * @return array<int,string>
   */
  protected function csvToArray(?string $csv): array {
    if ($csv === NULL || $csv === '') {
      return [];
    }
    $parts = preg_split('/\s*,\s*/', trim($csv));
    $parts = $parts === FALSE ? [] : $parts;
    /** @var array<int,string> $clean */
    $clean = array_values(array_filter($parts, static fn($p): bool => $p !== ''));
    return $clean;
  }

  protected function normalizeValue(?string $raw): string {
    $s = trim((string) $raw);
    if ($s === '') {
      return '[]';
    }
    if (preg_match('/^\s*\[.*\]\s*$/', $s) === 1) {
      return $s;
    }
    $parts = preg_split('/\s*,\s*/', $s);
    $parts = $parts === FALSE ? [] : $parts;
    $vals = [];
    foreach ($parts as $p) {
      if ($p === '') {
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

  /**
   * @return array<int,mixed>
   */
  protected function decodeJsonArray(string $json): array {
    $a = json_decode($json, TRUE);
    return is_array($a) ? array_values($a) : [];
  }

  /**
   * @return array<int,mixed>
   */
  protected function normalizeToArray(mixed $v): array {
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
      $parts = preg_split('/\s*,\s*/', $s);
      $parts = $parts === FALSE ? [] : $parts;
      return array_values(array_filter($parts, static fn($x): bool => $x !== ''));
    }
    return [];
  }

  /**
   * @return array<int,string>
   */
  protected function normalizeToStringArray(mixed $v): array {
    $a = $this->normalizeToArray($v);
    /** @var array<int,string> $strings */
    $strings = array_values(array_map([$this, 'toString'], $a));
    return $strings;
  }

  /**
   * @param array<int,string|int|array{id?:int}|mixed> $erValue
   * @return array<int,int>
   */
  protected function entityRefIdsToIntArray(mixed $erValue): array {
    if (is_string($erValue)) {
      $parts = preg_split('/\s*,\s*/', $erValue);
      $parts = $parts === FALSE ? [] : $parts;
      $parts = array_values(array_filter($parts, static fn($p): bool => $p !== ''));
      $ints  = array_map([$this, 'toInt'], $parts);
      return array_values(array_filter($ints, static fn(int $n): bool => $n > 0));
    }
    $out = [];
    foreach ((array) $erValue as $item) {
      if (is_array($item) && isset($item['id'])) {
        $out[] = $this->toInt($item['id']);
      }
      else {
        $out[] = $this->toInt($item);
      }
    }
    return array_values(array_filter($out, static fn(int $n): bool => $n > 0));
  }

  protected function optionValue(string $groupName, string $name): int {
    try {
      $r = \Civi\Api4\OptionValue::get()
        ->addWhere('option_group_id:name', '=', $groupName)
        ->addWhere('name', '=', $name)
        ->addSelect('value')->setLimit(1)->execute();
      $row = $this->firstRow($r);
      if (isset($row['value'])) {
        return $this->toInt($row['value']);
      }
    }
    catch (\Throwable $e) {
      Civi::log()->warning('optionValue warning', ['msg' => $e->getMessage()]);
      throw $e;
    }
    return 0;
  }

  protected function ensureQueue(): void {
    CRM_Core_DAO::executeQuery(
      "INSERT IGNORE INTO civicrm_queue (name,type,status,is_template) VALUES (%1,'Sql','active',0)",
      [1 => ['notification.jobs', 'String']]
    );
  }

  /**
   * @param array<int,string> $languages
   */
  protected function ensureRule(
    int $ruleSetId,
    string $title,
    array $languages,
    int $ruleId = 0,
    bool $isActive = TRUE
  ): int {
    if ($ruleId > 0) {
      \Civi\Api4\NotificationRule::update()
        ->addWhere('id', '=', $ruleId)
        ->addValue('rule_set_id', $ruleSetId)
        ->addValue('title', $title)
        ->addValue('is_active', $isActive)
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
    if ($existing->count() > 0) {
      $exRow = $this->firstRow($existing);
      \Civi\Api4\NotificationRule::update()
        ->addWhere('id', '=', $this->toInt($exRow['id'] ?? 0))
        ->addValue('rule_set_id', $ruleSetId)
        ->addValue('title', $title)
        ->addValue('is_active', $isActive)
        ->addValue('on_create', FALSE)
        ->addValue('on_update', TRUE)
        ->addValue('on_delete', FALSE)
        ->addValue('languages', $languages)->execute();
      return $this->toInt($exRow['id'] ?? 0);
    }

    $create = \Civi\Api4\NotificationRule::create()
      ->addValue('rule_set_id', $ruleSetId)
      ->addValue('title', $title)
      ->addValue('is_active', $isActive)
      ->addValue('on_create', FALSE)
      ->addValue('on_update', TRUE)
      ->addValue('on_delete', FALSE)
      ->addValue('languages', $languages)->execute();
    $crRow = $this->firstRow($create);
    return $this->toInt($crRow['id'] ?? 0);
  }

  protected function ensureRuleSet(string $title, string $entity): int {
    $existing = \Civi\Api4\NotificationRuleSet::get()
      ->addWhere('title', '=', $title)
      ->addWhere('monitored_entity_type', '=', $entity)
      ->addSelect('id')->setLimit(1)->execute();
    if ($existing->count() > 0) {
      $exRow = $this->firstRow($existing);
      \Civi\Api4\NotificationRuleSet::update()
        ->addWhere('id', '=', $this->toInt($exRow['id'] ?? 0))
        ->addValue('is_active', TRUE)
        ->addValue('is_execute_only_first_rule', FALSE)->execute();
      return $this->toInt($exRow['id'] ?? 0);
    }
    $create = \Civi\Api4\NotificationRuleSet::create()
      ->addValue('title', $title)
      ->addValue('monitored_entity_type', $entity)
      ->addValue('source_entity_type', $entity)
      ->addValue('source_entity_id', 0)
      ->addValue('is_active', TRUE)
      ->addValue('is_execute_only_first_rule', FALSE)->execute();
    $crRow = $this->firstRow($create);
    return $this->toInt($crRow['id'] ?? 0);
  }

  protected function resetContactSelection(int $ruleId): void {
    \Civi\Api4\NotificationContactSelection::delete()
      ->addWhere('rule_id', '=', $ruleId)->execute();
  }

  /**
   * @param array<int,int> $contactIds
   * @param array<int,int> $groupIds
   * @param array<int,int> $contactTypes
   */
  protected function createContactSelection(
    int $ruleId,
    array $contactIds,
    array $groupIds,
    array $contactTypes
  ): void {
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
    if ($existing->count() > 0) {
      $exRow = $this->firstRow($existing);
      \Civi\Api4\NotificationFieldMonitoring::update()
        ->addWhere('id', '=', $this->toInt($exRow['id'] ?? 0))
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
   * @param array<int,string> $languages
   */
  protected function replaceRuleMessageTemplate(int $ruleId, int $mtId, array $languages): void {
    \Civi\Api4\NotificationRuleMessageTemplate::delete()
      ->addWhere('rule_id', '=', $ruleId)->execute();

    \Civi\Api4\NotificationRuleMessageTemplate::create()
      ->addValue('rule_id', $ruleId)
      ->addValue('msg_template_id', $mtId)
      ->addValue('languages', $languages)->execute();
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

  /* ========= Helpers ========= */

  /**
   * @param \Civi\Api4\Generic\Result|iterable<mixed>|array<mixed>|null $result
   * @return array<string,mixed>
   */
  // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
  private function firstRow($result): array {
    // phpcs:enable
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
    if ($result instanceof \ArrayAccess) {
      if (isset($result[0])) {
        $row = $result[0];
        return is_array($row) ? $row : (array) $row;
      }
    }
    return [];
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
      if ($v === '') {
        return 0;
      }
      if (preg_match('/^-?\d+$/', $v) === 1) {
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
