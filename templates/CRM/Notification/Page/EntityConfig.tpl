{crmScope extensionKey='notification'}

  <div class="crm-block crm-content-block">
    <h3>{ts}Create rule{/ts}</h3>
    <div class="notification-entity-actions">
      <label for="notification-entity-select" class="notification-sr">{ts}Entity{/ts}</label>
      <select id="notification-entity-select" class="crm-form-select">
        <option value="">{ts}Select an entity…{/ts}</option>
        {foreach from=$entities item=e}
          <option value="{$e}">{$e}</option>
        {/foreach}
      </select>
      <button id="notification-create-rule" class="button" disabled>{ts}Add rule{/ts}</button>
    </div>
  </div>

  <div class="crm-block crm-content-block">
    <h3>{ts}Existing RuleSets{/ts}</h3>

    <table class="display" style="width:100%">
      <thead>
      <tr>
        <th>{ts}ID{/ts}</th>
        <th>{ts}Entity{/ts}</th>
        <th>{ts}Title{/ts}</th>
        <th>{ts}Rules{/ts}</th>
        <th>{ts}Only first?{/ts}</th>
        <th>{ts}Active{/ts}</th>
        <th>{ts}Actions{/ts}</th>
      </tr>
      </thead>
      <tbody>
      {foreach from=$ruleSets item=rs}
        {assign var=rid value=$rs.id}
        <tr>
          <td>{$rs.id}</td>
          <td>{$rs.monitored_entity_type}</td>
          <td>{$rs.title}</td>
          <td>{if isset($ruleCounts[$rid])}{$ruleCounts[$rid]}{else}0{/if}</td>
          <td>{if $rs.is_execute_only_first_rule}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
          <td>{if $rs.is_active}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
          <td class="notification-actions">
            <a class="button" href="{crmURL p='civicrm/notification/entity-rule' q="reset=1&entity_type=`$rs.monitored_entity_type`&ruleset_id=`$rs.id`"}">{ts}Add rule{/ts}</a>
            <a class="button button-secondary js-toggle-rules" data-ruleset="{$rs.id}" href="#">{ts}Show rules{/ts}</a>
          </td>
        </tr>

        <tr id="ruleset-rules-{$rs.id}" class="ruleset-rules is-hidden">
          <td colspan="7">
            <table class="display inner" style="width:100%">
              <thead>
              <tr>
                <th>{ts}ID{/ts}</th>
                <th>{ts}Title{/ts}</th>
                <th>{ts}Active{/ts}</th>
                <th>{ts}Actions{/ts}</th>
              </tr>
              </thead>
              <tbody>
              {assign var=rules value=$rulesBySet[$rid]|default:[]}
              {if $rules|@count}
                {foreach from=$rules item=rule}
                  <tr>
                    <td>{$rule.id}</td>
                    <td>{if $rule.title}{$rule.title}{else}{ts}Rule #{/ts}{$rule.id}{/if}</td>
                    <td>{if $rule.is_active}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
                    <td>
                      <a class="button" href="{crmURL p='civicrm/notification/entity-rule' q="reset=1&entity_type=`$rs.monitored_entity_type`&ruleset_id=`$rs.id`&rule_id=`$rule.id`"}">{ts}Edit{/ts}</a>
                    </td>
                  </tr>
                {/foreach}
              {else}
                <tr>
                  <td colspan="4">{ts}No rules in this set yet.{/ts}</td>
                </tr>
              {/if}
              </tbody>
            </table>
          </td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  </div>

{/crmScope}
