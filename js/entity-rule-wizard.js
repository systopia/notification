(function ($, CRM) {
  const cfg = (CRM.settings && CRM.settings.notification && CRM.settings.notification.entityRuleWizard) || (CRM.config && CRM.config.notification && CRM.config.notification.entityRuleWizard) || {};
  const NEW_RULESET_VALUE = (cfg.newRulesetValue || 'new');
  const ts = (CRM.ts && typeof CRM.ts === 'function') ? CRM.ts('notification') : function (s) { return s; };
  const STAR = ' ★';

  let currentEntity = '';

  const fieldOptionsByEntity = {};
  const hasOptionsByEntity = {};
  const fieldMetaByEntity = {};

  function ensureSelect2($el, multi) {
    try { if ($el.data('select2')) $el.select2('destroy'); } catch (e) {}
    if (multi) $el.attr('multiple', 'multiple'); else $el.removeAttr('multiple');
    if ($el.crmSelect2) $el.crmSelect2({ allowClear: true, placeholder: '' });
    else if ($el.select2) $el.select2({ allowClear: true, placeholder: '' });
  }

  function rowOf(sel) {
    const $el = $(sel);
    const $row = $el.closest('.crm-section');
    return $row.length ? $row : $el;
  }

  function toggleRulesetTitleRow() {
    const val = $('#ruleset_id').val();
    $('#row-ruleset-title').toggleClass('notification-hidden', !!(val && val !== NEW_RULESET_VALUE));
  }

  function showValueRows(show) {
    rowOf('#value_before_opts').toggle(!!show);
    rowOf('#value_after_opts').toggle(!!show);
  }

  function showRawRows(show) {
    rowOf('#value_before').toggle(!!show);
    rowOf('#value_after').toggle(!!show);
  }

  function tryParseArray(val) {
    if (!val) return [];
    try { const x = JSON.parse(val); return Array.isArray(x) ? x : []; } catch (e) { return []; }
  }

  function fillValuesSelects(options) {
    const $b = $('#value_before_opts'), $a = $('#value_after_opts');
    $b.empty(); $a.empty();
    options.forEach(function (opt) {
      $('<option>').attr('value', opt.id).text(opt.text).appendTo($b);
      $('<option>').attr('value', opt.id).text(opt.text).appendTo($a);
    });
    ensureSelect2($b, true);
    ensureSelect2($a, true);

    function coercePrefillToIds(prefill, opts) {
      if (!prefill || !prefill.length) return [];
      const byId = {}; const byText = {};
      opts.forEach(function (o) { byId[String(o.id)] = String(o.id); byText[String(o.text)] = String(o.id); });
      return prefill.map(function (x) { const sx = String(x); if (byId[sx]) return byId[sx]; if (byText[sx]) return byText[sx]; return null; }).filter(Boolean);
    }

    const preB = tryParseArray($b.attr('data-prefill'));
    const preA = tryParseArray($a.attr('data-prefill'));
    const mappedB = coercePrefillToIds(preB, options);
    const mappedA = coercePrefillToIds(preA, options);

    if (mappedB.length) { $b.val(mappedB).trigger('change'); }
    if (mappedA.length) { $a.val(mappedA).trigger('change'); }
  }

  function clearValuesSelects() {
    const $b = $('#value_before_opts'), $a = $('#value_after_opts');
    try { if ($b.data('select2')) $b.select2('destroy'); } catch (e) {}
    try { if ($a.data('select2')) $a.select2('destroy'); } catch (e) {}
    $b.empty(); $a.empty();
  }

  function normalizeOptions(o) {
    if (!o) return [];
    if ($.isArray(o)) {
      return o.map(function (x) {
        let id = null; if (x.key != null) id = x.key; else if (x.id != null) id = x.id; else if (x.value != null) id = x.value;
        let text = x.label || x.name || x.value || id;
        if (id == null || text == null) return null;
        return { id: String(id), text: String(text) };
      }).filter(Boolean);
    }
    return $.map(o, function (label, id) { return { id: String(id), text: String(label) }; });
  }

  function markStar($field, name) {
    const by = hasOptionsByEntity[currentEntity] || {};
    const $opt = $field.find('option[value="' + name + '"]');
    if (!$opt.length) return;
    const txt = $opt.text();
    if (by[name]) { if (!txt.endsWith(STAR)) $opt.text(txt + STAR); }
    else { if (txt.endsWith(STAR)) $opt.text(txt.slice(0, -STAR.length)); }
  }

  function setRawInputsFor(fieldName, clearValues) {
    const metaBy = fieldMetaByEntity[currentEntity] || {};
    const meta = metaBy[fieldName] || {};
    const dt = String(meta.data_type || '').toLowerCase();
    const $b = $('#value_before');
    const $a = $('#value_after');
    function setMode($el, type, step, placeholder) {
      $el.attr('type', type);
      if (step != null) $el.attr('step', step); else $el.removeAttr('step');
      $el.attr('placeholder', placeholder || '');
      if (clearValues) $el.val('');
    }
    if (dt === 'integer' || dt === 'int') {
      setMode($b, 'number', 1, ts('e.g. 1,2,3'));
      setMode($a, 'number', 1, ts('e.g. 4,5'));
    } else if (dt === 'float' || dt === 'money' || dt === 'decimal' || dt === 'double') {
      setMode($b, 'number', 'any', ts('e.g. 1.5,2.75'));
      setMode($a, 'number', 'any', ts('e.g. 3.0'));
    } else if (dt === 'date') {
      setMode($b, 'date', null, ts('YYYY-MM-DD, comma-separated'));
      setMode($a, 'date', null, ts('YYYY-MM-DD, comma-separated'));
    } else if (dt === 'timestamp' || dt === 'datetime') {
      setMode($b, 'datetime-local', null, ts('YYYY-MM-DDThh:mm, comma-separated'));
      setMode($a, 'datetime-local', null, ts('YYYY-MM-DDThh:mm, comma-separated'));
    } else if (dt === 'boolean' || dt === 'bool') {
      setMode($b, 'text', null, ts('0 or 1, comma-separated'));
      setMode($a, 'text', null, ts('0 or 1, comma-separated'));
    } else {
      setMode($b, 'text', null, ts('comma-separated values'));
      setMode($a, 'text', null, ts('comma-separated values'));
    }
  }

  function loadFieldOptions(fieldName, preserveExisting) {
    if (!fieldName || !currentEntity) {
      showValueRows(false); clearValuesSelects(); showRawRows(true); setRawInputsFor('', !preserveExisting); return;
    }
    const byField = fieldOptionsByEntity[currentEntity] || {};
    const cached = byField[fieldName] || [];
    const $field = $('#field_name');

    if (cached.length) {
      if (!preserveExisting) $('#value_before,#value_after').val('');
      showRawRows(false); showValueRows(true); fillValuesSelects(cached);
      return;
    }

    CRM.api3(currentEntity, 'getoptions', { field: fieldName, context: 'validate', sequential: 1 })
      .then(function (res) {
        const v = res.values || {};
        let opts = [];
        if ($.isArray(v)) {
          opts = v.map(function (o) {
            var id = null;
            if (o.key != null) id = o.key;
            else if (o.id != null) id = o.id;
            else if (o.value != null && typeof o.value !== 'string') id = o.value;
            var text = o.label || o.value || o.name || id;
            if (id == null || text == null) return null;
            return { id: String(id), text: String(text) };
          }).filter(Boolean);
        } else {
          opts = $.map(v, function (label, id) { return { id: String(id), text: String(label) }; });
        }
        if (opts.length) {
          if (!fieldOptionsByEntity[currentEntity]) fieldOptionsByEntity[currentEntity] = {};
          fieldOptionsByEntity[currentEntity][fieldName] = opts;
          markStar($field, fieldName);
          if (!preserveExisting) $('#value_before,#value_after').val('');
          showRawRows(false); showValueRows(true); fillValuesSelects(opts);
        } else {
          markStar($field, fieldName);
          clearValuesSelects();
          $('#value_before_opts,#value_after_opts').val(null).trigger('change');
          showValueRows(false); showRawRows(true); setRawInputsFor(fieldName, !preserveExisting);
        }
      }, function () {
        clearValuesSelects(); showValueRows(false); showRawRows(true); setRawInputsFor(fieldName, !preserveExisting);
      });
  }

  function populateMessageTemplates() {
    const $sel = $('#message_template_id');
    const def = ($sel.attr('data-default') || '').trim();
    try { if ($sel.data('select2')) $sel.select2('destroy'); } catch (e) {}
    $sel.empty().append($('<option>').attr('value', '').text(ts('- select Message Template -')));
    CRM.api3('MessageTemplate', 'get', {
      sequential: 1, return: ['id', 'msg_title'], is_active: 1, options: { sort: 'msg_title', limit: 0 }
    }).done(function (res) {
      (res.values || []).forEach(function (row) {
        if (row.msg_title && String(row.msg_title).trim() !== '') {
          $('<option>').attr('value', row.id).text(row.msg_title).appendTo($sel);
        }
      });
      ensureSelect2($sel, false);
      if (def) { $sel.val(def).trigger('change'); }
    }).fail(function () { ensureSelect2($sel, false); });
  }

  function esc(s) { return String(s || '').replace(/[&<>"']/g, function (c) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]); }); }
  function chip(cls, txt) { return '<span class="notif-chip ' + cls + '">' + esc(txt) + '</span>'; }
  function prettyOp(op) { switch (op) { case 'in': return 'in'; case 'not_in': return 'not in'; case 'eq': return '='; case 'neq': return '≠'; default: return op || ''; } }
  function parseIdsFromRaw(raw) {
    const s = $.trim(String(raw || '')); if (!s) return [];
    if (s[0] === '[') { try { const a = JSON.parse(s); return Array.isArray(a) ? a.map(String) : []; } catch (e) { return []; } }
    return s.split(/\s*,\s*/).filter(Boolean).map(String);
  }

  function updateSummary() {
    const $sum = $('#notif-summary');
    const entity = ($('input[name="entity_type"]').val() || currentEntity || 'Entity');
    const fieldLabel = $('#field_name option:selected').text() || 'field';
    const preOp  = String($('#operator_before').val() || '');
    const postOp = String($('#operator_after').val()  || '');
    const $vb = $('#value_before_opts'), $va = $('#value_after_opts');
    let beforeIds = ($vb.val() || []).map(String);
    let afterIds  = ($va.val()  || []).map(String);
    const beforeLabels = $vb.find('option:selected').map(function(){ return $(this).text(); }).get();
    const afterLabels  = $va.find('option:selected').map(function(){ return $(this).text(); }).get();
    if (!beforeIds.length) beforeIds = parseIdsFromRaw($('#value_before').val());
    if (!afterIds.length)  afterIds  = parseIdsFromRaw($('#value_after').val());
    if ((beforeIds.length || afterIds.length)) {
      const html = ts('Rule matches when') + ' ' + chip('entity', entity) + ' ' +
        ts('changes the field') + ' ' + chip('field', fieldLabel) + ' ' +
        ts('from') + ' ' + chip('op', prettyOp(preOp)) + ' "' + esc(beforeLabels.join(', ') || '—') + '"' +
        ' <span class="notif-ids">[' + esc(beforeIds.join(', ')) + ']</span> ' +
        ts('to') + ' ' + chip('op', prettyOp(postOp)) + ' "' + esc(afterLabels.join(', ') || '—') + '"' +
        ' <span class="notif-ids">[' + esc(afterIds.join(', ')) + ']</span>.';
      $sum.html(html);
    } else {
      $sum.text('');
    }
  }

  function populateFields() {
    const $field = $('#field_name');
    const savedField = $field.attr('data-default') || $field.val() || '';

    try { if ($field.data('select2')) $field.select2('destroy'); } catch (e) {}
    $field.empty();
    $('<option>').attr('value', '').text(ts('- select field -')).appendTo($field);

    if (!currentEntity) { ensureSelect2($field, false); showValueRows(false); showRawRows(false); return; }

    CRM.api4(currentEntity, 'getFields', {
      action: 'get', checkPermissions: false,
      select: ['name', 'label', 'options', 'readonly', 'is_virtual', 'data_type'], loadOptions: true
    }).then(function(rows) {
      const usable = rows.filter(function(f){ return !f.readonly && !f.is_virtual; });
      const byField = {}, hasBy = {}, metaBy = {};
      usable.forEach(function(f){
        const opts = (function(o){ if (!o) return []; if ($.isArray(o)) return o.map(x => ({id:String(x.key ?? x.id ?? x.value), text:String(x.label ?? x.name ?? x.value ?? '')})).filter(x=>x.id&&x.text); return $.map(o, (label,id)=>({id:String(id),text:String(label)})); })(f.options);
        if (opts.length) byField[f.name] = opts, hasBy[f.name] = true;
        metaBy[f.name] = { data_type: f.data_type || '' };
        $('<option>').attr('value', f.name).text((f.label||f.name) + (opts.length?STAR:'')).appendTo($field);
      });
      fieldOptionsByEntity[currentEntity] = byField;
      hasOptionsByEntity[currentEntity] = hasBy;
      fieldMetaByEntity[currentEntity] = metaBy;

      ensureSelect2($field, false);
      if (savedField && $field.find('option[value="'+savedField+'"]').length) {
        $field.val(savedField); loadFieldOptions(savedField, true);
      }
    }).catch(function(){
      CRM.api3(currentEntity, 'getfields', { api_action: 'get', sequential: 1, options: { limit: 0 } })
        .then(function(res){
          const values = res.values || {};
          const byField = {}, hasBy = {}, metaBy = {};
          Object.keys(values).forEach(function(name){
            const f = values[name] || {};
            const opts = (function(o){ if (!o) return []; return $.map(o, (label,id)=>({id:String(id),text:String(label)})); })(f.options || {});
            if (opts.length) byField[name] = opts, hasBy[name] = true;
            metaBy[name] = { data_type: (f.type || f.data_type || '') };
            $('<option>').attr('value', name).text((f.title||name) + (opts.length?STAR:'')).appendTo($field);
          });
          fieldOptionsByEntity[currentEntity] = byField;
          hasOptionsByEntity[currentEntity] = hasBy;
          fieldMetaByEntity[currentEntity] = metaBy;

          ensureSelect2($field, false);
          if (savedField && $field.find('option[value="'+savedField+'"]').length) {
            $field.val(savedField); loadFieldOptions(savedField, true);
          }
        });
    });
  }

  $(function () {
    var ent = (cfg.entity || '').trim();
    if (!ent) {
      var $et = $('input[name="entity_type"]');
      ent = $et.length ? $.trim($et.val()) : '';
    }
    if (!ent) {
      var m = location.search.match(/(?:[?&])entity_type=([^&]+)/);
      if (m) ent = decodeURIComponent(m[1].replace(/\+/g, ' '));
    }
    currentEntity = ent || 'Activity';

    $('#ruleset_id').on('change', toggleRulesetTitleRow);
    toggleRulesetTitleRow();

    ensureSelect2($('#value_before_opts'), true);
    ensureSelect2($('#value_after_opts'),  true);

    $(document).on('change', '#field_name', function () {
      const v = $(this).val();
      loadFieldOptions(v, false);
    });

    $(document).on('change', '#operator_before,#operator_after,#value_before_opts,#value_after_opts', updateSummary);
    $(document).on('input',  '#value_before,#value_after', updateSummary);

    populateFields();
    populateMessageTemplates();

    $(document).on('click', 'input[name="_qf_EntityRuleWizard_next_delete"], #notif-delete-rule', function (e) {
      var msg = (CRM.ts && CRM.ts('notification'))
        ? CRM.ts('notification')('Are you sure you want to delete this rule? This cannot be undone.')
        : 'Are you sure you want to delete this rule? This cannot be undone.';
      if (!window.confirm(msg)) {
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
      }
    });
  });
})(CRM.$, CRM);
