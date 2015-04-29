{include file=$tpl_config.header}
<div id="stats-main">
    <h1 class="text-center">{t}Statistics{/t}
        <small class="">{$stats.online} online user{if $stats.online != 1}s{/if}</small>
    </h1>
    <div id="stats-body">
        {$stats.body}
    </div>
</div>
{include file=$tpl_config.footer}