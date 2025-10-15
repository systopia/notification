{* templates/CRM/Notification/Page/Logs.tpl *}

{literal}
  <style>
    .notif-filters { display:flex; flex-wrap:wrap; gap:.75rem; align-items:flex-end; margin:0 0 12px 0; }
    .notif-filters .field { display:flex; flex-direction:column; gap:4px; }
    .notif-filters .field label { font-weight:600; font-size:12px; color:#444; }
    .notif-filters .actions { display:flex; align-items:flex-end; gap:.5rem; }
    .notif-level { display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; line-height:18px; color:#fff; text-transform:lowercase; }
    .notif-level.debug{background:#6c757d}.notif-level.info{background:#17a2b8}.notif-level.warning{background:#fd7e14}.notif-level.error{background:#dc3545}

    .crm-container table.report.layout.notif-table { width:100% !important; table-layout:fixed; box-sizing:border-box; }
    .crm-container table.report.layout.notif-table th,
    .crm-container table.report.layout.notif-table td { overflow-wrap:anywhere; vertical-align:top; }
    .crm-container table.report.layout.notif-table td code {
      white-space:pre-wrap; display:block; max-height:12em; overflow:auto;
      padding:.5em; background:#f6f8fa; border:1px solid #e1e4e8; border-radius:4px;
    }

    #f-level + .select2-container { min-width: 220px; }
    .notif-pager { display:flex; gap:.5rem; align-items:center; margin-top:10px; flex-wrap:wrap; }
    .notif-pager .disabled { pointer-events:none; opacity:.45; }
    .notif-meta { margin-top:6px; color:#555; font-size:12px; }
    .notif-sort-arrow { margin-left:4px; font-size:11px; opacity:.8; }
  </style>
{/literal}

<form method="get" action="{$selfUrl|escape}">
  <div class="notif-filters">
    <div class="field">
      <label for="f-q">{ts}Search{/ts}</label>
      <input id="f-q" type="text" name="q" value="{$q|escape}" placeholder="{ts}Message / Area / Context…{/ts}">
    </div>

    <div class="field">
      <label for="f-level">{ts}Level{/ts}</label>
      <select id="f-level" name="level[]" multiple="multiple" class="crm-select2">
        {foreach from=$levelOptions item=lv}
          <option value="{$lv|escape}" {if in_array($lv,$level)}selected{/if}>{$lv|escape}</option>
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

<table class="report layout notif-table">
  <colgroup>
    <col style="width:70px;">
    <col style="width:170px;">
    <col style="width:90px;">
    <col style="width:200px;">
    <col style="width:25%;">
    <col style="width:40%;">
    <col style="width:120px;">
  </colgroup>
  <thead>
  <tr>
    <th>
      <a href="{$selfUrl|escape}?{$baseQ|escape}&sort=id&order={$orderMap.id|escape}&page=1">
        {ts}ID{/ts}{if $sort=='id'}<span class="notif-sort-arrow">{if $order=='asc'}▲{else}▼{/if}</span>{/if}
      </a>
    </th>
    <th>
      <a href="{$selfUrl|escape}?{$baseQ|escape}&sort=created_at&order={$orderMap.created_at|escape}&page=1">
        {ts}Date{/ts}{if $sort=='created_at'}<span class="notif-sort-arrow">{if $order=='asc'}▲{else}▼{/if}</span>{/if}
      </a>
    </th>
    <th>
      <a href="{$selfUrl|escape}?{$baseQ|escape}&sort=level&order={$orderMap.level|escape}&page=1">
        {ts}Level{/ts}{if $sort=='level'}<span class="notif-sort-arrow">{if $order=='asc'}▲{else}▼{/if}</span>{/if}
      </a>
    </th>
    <th>
      <a href="{$selfUrl|escape}?{$baseQ|escape}&sort=area&order={$orderMap.area|escape}&page=1">
        {ts}Area{/ts}{if $sort=='area'}<span class="notif-sort-arrow">{if $order=='asc'}▲{else}▼{/if}</span>{/if}
      </a>
    </th>
    <th>
      <a href="{$selfUrl|escape}?{$baseQ|escape}&sort=message&order={$orderMap.message|escape}&page=1">
        {ts}Message{/ts}{if $sort=='message'}<span class="notif-sort-arrow">{if $order=='asc'}▲{else}▼{/if}</span>{/if}
      </a>
    </th>
    <th>{ts}Context{/ts}</th>
    <th class="nowrap">{ts}Actions{/ts}</th>
  </tr>
  </thead>
  <tbody>
  {if $rows|@count == 0}
    <tr><td colspan="7">{ts}No results.{/ts}</td></tr>
  {else}
    {foreach from=$rows item=r}
      <tr>
        <td>{$r.id}</td>
        <td>{$r.created_at|escape}</td>
        <td><span class="notif-level {$r.level|escape}">{$r.level|escape}</span></td>
        <td>{$r.area|escape}</td>
        <td>{$r.message|escape}</td>
        <td><code>{$r.contextPreview|escape}</code></td>
        <td class="nowrap">
          <textarea id="json-{$r.id}" class="notif-json-store" style="display:none;">{$r.contextJson|escape:'html'}</textarea>
          <button type="button" class="crm-button" data-action="view" data-id="{$r.id}">{ts}View{/ts}</button>
          <button type="button" class="crm-button" data-action="copy" data-id="{$r.id}">{ts}Copy{/ts}</button>
        </td>
      </tr>
    {/foreach}
  {/if}
  </tbody>
</table>

<div class="notif-pager">
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

<div class="notif-meta">
  {if $count}{ts}Showing{/ts} {$fromItem}-{$toItem} {ts}of{/ts} {$count}{/if}
</div>
