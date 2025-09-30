{crmScope extensionKey='notification'}
  <div class="crm-block crm-form-block notification-rule-form">

    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

    <!-- Context -->
    <fieldset class="notif-fieldset">
      <legend>{ts}Context{/ts}</legend>
      <table class="form-layout">
        <tr>
          <td class="label">{$form.entity_type.label}</td>
          <td class="content">{$form.entity_type.html}</td>
          <td class="label">{$form.ruleset_id.label}</td>
          <td class="content">{$form.ruleset_id.html}</td>
        </tr>
        <tr id="row-ruleset-title">
          <td class="label">{$form.ruleset_title.label}</td>
          <td class="content" colspan="3">{$form.ruleset_title.html}
            <div class="description">{ts}Used if no RuleSet is selected above{/ts}</div>
          </td>
        </tr>
        <tr>
          <td class="label">{$form.rule_title.label}</td>
          <td class="content" colspan="3">{$form.rule_title.html}</td>
        </tr>
      </table>
    </fieldset>

    <!-- Recipients -->
    <fieldset class="notif-fieldset">
      <legend>{ts}Recipients{/ts}</legend>
      <table class="form-layout">
        <tr>
          <td class="label">{$form.contact_ids_er.label}</td>
          <td class="content">{$form.contact_ids_er.html}</td>
          <td class="label">{$form.group_ids_er.label}</td>
          <td class="content">{$form.group_ids_er.html}</td>
        </tr>
        <tr>
          <td class="label">{$form.languages.label}</td>
          <td class="content" colspan="3">{$form.languages.html}</td>
        </tr>
      </table>
    </fieldset>

    <!-- Trigger -->
    <fieldset class="notif-fieldset">
      <legend>{ts}When does this rule trigger?{/ts}</legend>

      <div class="row">
        <div class="col label">{$form.field_name.label}</div>
        <div class="col content">
          {$form.field_name.html} <em>⭐ {ts}star indicates field has option values{/ts}</em>
        </div>
      </div>

      <div class="notif-grid">
        <div class="notif-col">
          <h4>{ts}Before{/ts}</h4>
          <div class="row">
            <div class="col label">{$form.operator_before.label}</div>
            <div class="col content">{$form.operator_before.html}</div>
          </div>

          <div id="row-value-before-opts" class="row notification-hidden">
            <div class="col label">{ts}Value Before (by label){/ts}</div>
            <div class="col content">{$form.value_before_opts.html}</div>
          </div>

          <div class="row">
            <div class="col label"><div class="col label">{ts}Enter values manually{/ts}</div></div>
            <div class="col content"><div id="adv-before" class="notif-advanced">{$form.value_before.html}</div></div>
          </div>
        </div>

        <div class="notif-col">
          <h4>{ts}After{/ts}</h4>
          <div class="row">
            <div class="col label">{$form.operator_after.label}</div>
            <div class="col content">{$form.operator_after.html}</div>
          </div>

          <div id="row-value-after-opts" class="row notification-hidden">
            <div class="col label">{ts}Value After (by label){/ts}</div>
            <div class="col content">{$form.value_after_opts.html}</div>
          </div>

          <div class="row">
            <div class="col label"><div class="col label">{ts}Enter values manually{/ts}</div></div>
            <div class="col content"><div id="adv-after" class="notif-advanced">{$form.value_after.html}</div></div>
          </div>
        </div>
      </div>
    </fieldset>

    <!-- Message -->
    <fieldset class="notif-fieldset">
      <legend>{ts}Message{/ts}</legend>
      <table class="form-layout">
        <tr>
          <td class="label">{$form.message_template_id.label}</td>
          <td class="content" colspan="3">{$form.message_template_id.html}
            <div class="description">{ts}Search templates by title. Only templates with a title are listed.{/ts}</div>
          </td>
        </tr>
      </table>
    </fieldset>

    <!-- Summary -->
    <fieldset class="notif-fieldset">
      <legend>{ts}Summary{/ts}</legend>
      <div id="notif-summary" class="notif-summary"></div>
    </fieldset>


    <fieldset class="notif-fieldset">
      <legend>{ts}Rule maintenance{/ts}</legend>
      <table class="form-layout">
        <tr>
          <td class="label">{$form.is_active.label}</td>
          <td class="content">
            {$form.is_active.html}
            <div class="description">{ts}Toggle to enable/disable this rule.{/ts}</div>
          </td>
          {if $form.rule_id.value}
            <td class="label">&nbsp;</td>
            <td class="content">
              {crmButton type="next" subName="delete" class="crm-button crm-button-type-delete" id="notif-delete-rule" icon="trash" title="{ts}Delete rule permanently{/ts}"}
              {ts}Delete rule{/ts}
              {/crmButton}
              <div class="description">{ts}Deletes this rule permanently.{/ts}</div>

            </td>
          {/if}
        </tr>
      </table>
    </fieldset>

    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  </div>
{/crmScope}
