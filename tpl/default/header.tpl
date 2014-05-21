<!DOCTYPE html>
<html>
<head>
    <title>{$title|default:"SuperTuxKart Add-ons"}</title>
    {foreach $meta_tags as $meta_field => $meta_content}
        <meta http-equiv="{$meta_field}" content="{$meta_content}">
    {/foreach}
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    {foreach $css_includes as $css}
        <link rel="stylesheet" media="{$css.media}" href="{$css.href}">
    {/foreach}
</head>
<body>
{include file=#top_menu#}
<div id="body-frame">