{config_load file="tpl/default/tpl.conf"}
{include file=#header#}

<div id="panels">
    <div id="left-menu">
        <div id="left-menu_top"></div>
        <div id="left-menu_body">{$panel.left}</div>
        <div id="left-menu_bottom"></div>
    </div>
    <div id="right-content">
        <div id="right-content_top"></div>
        <div id="right-content_status">{$panel.status}</div>
        <div id="right-content_body">{$panel.right}</div>
        <div id="right-content_bottom"></div>
    </div>
</div>
{include file=#footer#}