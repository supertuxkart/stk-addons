<!DOCTYPE html>
<html>
<head>
    <title>{$title|default:"SuperTuxKart Add-ons"}</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    {foreach $meta_tags as $meta_field => $meta_content}
        <meta http-equiv="{$meta_field}" content="{$meta_content}">
    {/foreach}
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
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
{if isset($show_stk_image) && $show_stk_image == true}
    <div id="content-wrapper" class="stk-image" style="width: 1001px; margin: 0 auto;">
{else}
    <div id="content-wrapper">
{/if}
