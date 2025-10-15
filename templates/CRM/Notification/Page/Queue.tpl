{* templates/CRM/Notification/Page/Queue.tpl *}

<div class="crm-block crm-content-block">

  <form method="get" action="{$selfUrl|escape}">
    <div class="notifq-filters">
      <div class="field">
        <label for="f-q">{ts}Search{/ts}</label>
        <input id="f-q" type="text" name="q" value="{$q|escape}" placeholder="{ts}Queue name / Payload…{/ts}">
      </div>

      <div class="field">
        <label for="f-status">{ts}Status{/ts}</label>
        <select id="f-status" name="status[]" multiple="multiple" class="crm-select2">
          {foreach from=$statusOptions item=st}
            <option value="{$st|escape}" {if in_array($st,$status)}selected{/if}>{$st|escape}</option>
          {/foreach}
        </select>
      </div>

      <div class="field">
        <label for="f-limit">{ts}Per page{/ts}</label>
        <select id="f-limit" name="limit">
          {foreach from=$limitOptions item=opt}
            <option value="{$opt}" {if $opt == $limit}selected{/if}>{$opt}</option>
          {/foreach}
        </select>
      </div>

      <input type="hidden" name="page" value="1">
      <input type="hidden" name="sort" value="{$sort|escape}">
      <input type="hidden" name="order" value="{$order|escape}">

      <div class="actions">
        <button type="submit" class="crm-button crm-button-type-next">{ts}Apply{/ts}</button>
        <a href="{$resetUrl|escape}" class="crm-button crm-button-type-cancel">{ts}Reset{/ts}</a>
      </div>
    </div>
  </form>

  <table class="report layout notifq-table">
    <colgroup>
      <col style="width:70px;">
      <col style="width:170px;">
      <col style="width:170px;">
      <col style="width:140px;">
      <col style="width:40%;">
      <col style="width:120px;">
    </colgroup>
    <thead>
    <tr>
      <th>
        <a href="{$selfUrl|escape}?{$baseQ|escape}&sort=id&order={$orderMap.id|escape}&page=1">
          {ts}ID{/ts}{if $sort=='id'}<span class="notifq-sort-arrow">{if $order=='asc'}▲{else}▼{/if}</span>{/if}
        </a>
      </th>
      <th>
        <a href="{$selfUrl|escape}?{$baseQ|escape}&sort=submit_time&order={$orderMap.submit_time|escape}&page=1">
          {ts}Submitted{/ts}{if $sort=='submit_time'}<span class="notifq-sort-arrow">{if $order=='asc'}▲{else}▼{/if}</span>{/if}
        </a>
      </th>
      <th>
        <a href="{$selfUrl|escape}?{$baseQ|escape}&sort=release_time&order={$orderMap.release_time|escape}&page=1">
          {ts}Release{/ts}{if $sort=='release_time'}<span class="notifq-sort-arrow">{if $order=='asc'}▲{else}▼{/if}</span>{/if}
        </a>
      </th>
      <th>
        <a href="{$selfUrl|escape}?{$baseQ|escape}&sort=run_count&order={$orderMap.run_count|escape}&page=1">
          {ts}Status{/ts}{if $sort=='run_count'}<span class="notifq-sort-arrow">{if $order=='asc'}▲{else}▼{/if}</span>{/if}
        </a>
      </th>
      <th>{ts}Payload{/ts}</th>
      <th class="nowrap">{ts}Actions{/ts}</th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$rows item=row}
      <tr>
        <td>{$row.id}</td>
        <td>{$row.submitted|escape}</td>
        <td>{$row.release|escape}</td>
        <td>{$row.statusText|escape}</td>
        <td><code>{$row.preview|escape}</code></td>
        <td class="nowrap">
          <textarea id="payload-{$row.id}" class="notifq-store" style="display:none;">{$row.payloadJson|escape:'html'}</textarea>
          <button type="button" class="crm-button" data-action="view" data-id="{$row.id}">{ts}View{/ts}</button>
          <button type="button" class="crm-button" data-action="copy" data-id="{$row.id}">{ts}Copy{/ts}</button>
        </td>
      </tr>
      {foreachelse}
      <tr><td colspan="6">{ts}No items in this queue.{/ts}</td></tr>
    {/foreach}
    </tbody>
  </table>

  <div class="notifq-pager">
    {if $hasPrev}
      <a class="crm-button" href="{$selfUrl|escape}?{$baseQ|escape}&page=1">{ts}« First{/ts}</a>
      <a class="crm-button" href="{$selfUrl|escape}?{$baseQ|escape}&page={$prevPage}">{ts}‹ Prev{/ts}</a>
    {else}
      <span class="crm-button disabled">{ts}« First{/ts}</span>
      <span class="crm-button disabled">{ts}‹ Prev{/ts}</span>
    {/if}

    <span>{ts}Page{/ts} {$page} {ts}of{/ts} {$totalPages}</span>

    {if $hasNext}
      <a class="crm-button" href="{$selfUrl|escape}?{$baseQ|escape}&page={$nextPage}">{ts}Next ›{/ts}</a>
      <a class="crm-button" href="{$selfUrl|escape}?{$baseQ|escape}&page={$totalPages}">{ts}Last »{/ts}</a>
    {else}
      <span class="crm-button disabled">{ts}Next ›{/ts}</span>
      <span class="crm-button disabled">{ts}Last »{/ts}</span>
    {/if}
  </div>

  <div class="notifq-meta">
    {if $count}{ts}Showing{/ts} {$fromItem}-{$toItem} {ts}of{/ts} {$count}{/if}
  </div>
</div>
