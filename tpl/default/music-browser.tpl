{config_load file="tpl/default/tpl.conf"}
{include file=#header#}

<div id="content">
    <h1>{$music_browser.heading}</h1>
    {html_table loop=$music_browser.data cols=$music_browser.cols table_attr='border="0"'}

</div>{* #content *}
{include file=#footer#}