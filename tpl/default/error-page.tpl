{config_load file="tpl/default/tpl.conf"}
{include file=#header#}

<div id="error-container">
    <!-- Image by Bryan Lunduke [lunduke.com]? Not sure of original source. -->
    <img src="{#tpl_image_dir#}sad-tux.png" alt="Sad Tux" width="200" height="160" />
    <h1>{$error.title}</h1>
    <p>{$error.message}</p>
</div>{* #error-container *}
{include file=#footer#}