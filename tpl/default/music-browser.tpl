{include file=$tpl_config.header}
<div>
    <h1>{t}Browse Music{/t}</h1>
    {html_table loop=$music_browser.data cols=$music_browser.cols table_attr='border="0"'}
</div>
{include file=$tpl_config.footer}