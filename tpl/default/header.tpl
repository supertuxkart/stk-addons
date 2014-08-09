<!DOCTYPE html>
<html>
<head>
    <title>{$title|default:"SuperTuxKart Add-ons"}</title>
    <meta charset="UTF-8" />
    {foreach $meta_tags as $meta_field => $meta_content}
        <meta http-equiv="{$meta_field}" content="{$meta_content}">
    {/foreach}
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {foreach $css_includes as $css}
        {if empty($css.media)}
            <link rel="stylesheet" href="{$css.href}">
        {else}
            <link rel="stylesheet" media="{$css.media}" href="{$css.href}">
        {/if}
    {/foreach}
</head>
<body>
<div id="body-wrapper">
{include file=#top_menu#}
<div id="content-wrapper" class="container">
