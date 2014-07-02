{config_load file="{$smarty.current_dir}/tpl.conf"}
{include file=#header#}
<div id="stats-main">
    <h1 class="text-center">{t}Statistics{/t}</h1>
    <div class="container" id="stats-body">
        {$stats.body}
    </div>
</div>
{include file=#footer#}