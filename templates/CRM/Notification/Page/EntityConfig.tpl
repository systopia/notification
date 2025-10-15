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
    <br>
    {if $ruleSetsByEntity|@count}
      {foreach from=$ruleSetsByEntity key=entity item=list}
        <div class="crm-accordion-wrapper notification-entity-group" id="entity-{$entity|escape}">
          <div class="crm-accordion-header">
            <h4 class="notification-entity-title">
              {$entity}
              <span class="notification-chip">{ts}RuleSets:{/ts} </span>
            </h4>
          </div>

          <div class="crm-accordion-body">
            <div class="notification-ruleset-grid">
              {foreach from=$list item=rs}
                {assign var=rid value=$rs.id}
                {assign var=rc value=$ruleCounts[$rid]|default:0}

                <div class="notification-ruleset-card" data-ruleset="{$rid}">
                  <div class="notification-ruleset-head">
                    <div class="notification-ruleset-title">{$rs.title|escape}</div>
                    <div class="notification-ruleset-meta">
                      <span class="notification-badge">{$rc} {ts}rules{/ts}</span>
                      <span class="notification-badge {if $rs.is_active}is-on{else}is-off{/if}">
                        {if $rs.is_active}{ts}Active{/ts}{else}{ts}Inactive{/ts}{/if}
                      </span>
                      {if $rs.is_execute_only_first_rule}
                        <span class="notification-badge is-warn" title="{ts}Only first matching rule will run{/ts}">{ts}Only first{/ts}</span>
                      {else}
                        <span class="notification-badge is-muted" title="{ts}All matching rules will run{/ts}">{ts}All rules{/ts}</span>
                      {/if}
                    </div>
                  </div>

                  <div class="notification-ruleset-actions">
                    <a class="button" href="{crmURL p='civicrm/notification/entity-rule' q="reset=1&entity_type=`$rs.monitored_entity_type`&ruleset_id=`$rs.id`"}">{ts}Add rule{/ts}</a>
                    <button
                      class="button button-secondary js-toggle-rules"
                      data-target="#ruleset-rules-{$rid}"
                      data-count="{$rc}"
                      aria-controls="ruleset-rules-{$rid}"
                      aria-expanded="false">
                      {ts 1=$rc}Show %1 rules{/ts}
                    </button>
                  </div>

                  <div id="ruleset-rules-{$rid}" class="notification-ruleset-body is-hidden" aria-hidden="true">
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
                            <td>{if $rule.title}{$rule.title|escape}{else}{ts}Rule #{/ts}{$rule.id}{/if}</td>
                            <td>
                              {if $rule.is_active}
                                <span class="notification-dot is-on"></span> {ts}Yes{/ts}
                              {else}
                                <span class="notification-dot is-off"></span> {ts}No{/ts}
                              {/if}
                            </td>
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
                  </div>
                </div>

              {/foreach}
            </div>
          </div>
        </div>
      {/foreach}
    {else}
      <p>{ts}No RuleSets found.{/ts}</p>
    {/if}
  </div>

{/crmScope}
