{config_load file="{$smarty.current_dir}/tpl.conf"}
{include file=#header#}

<div id="content">
    <h1>{t}Browse Music{/t}</h1>
    {html_table loop=$music_browser.data cols=$music_browser.cols table_attr='border="0"'}

</div>{* #content *}
{include file=#footer#}