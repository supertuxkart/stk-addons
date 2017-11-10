<!DOCTYPE html>
<html>
<head>
    <title>{$title|default:"SuperTuxKart Add-ons"}</title>

    {* Set character encoding for the document *}
    <meta charset="utf-8">
    {foreach $meta_tags as $meta_field => $meta_content}
        <meta http-equiv="{$meta_field}" content="{$meta_content}">
    {/foreach}

    {* Instruct Internet Explorer to use its latest rendering engine *}
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1">
    {foreach $css_includes as $css}
        {if empty($css.media)}
            <link rel="stylesheet" href="{$css.href}">
        {else}
            <link rel="stylesheet" media="{$css.media}" href="{$css.href}">
        {/if}
    {/foreach}

    {* Favicon *}
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="icon" sizes="16x16 32x32 64x64" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="196x196" href="{$favicon_location}favicon-192.png">
    <link rel="icon" type="image/png" sizes="160x160" href="{$favicon_location}favicon-160.png">
    <link rel="icon" type="image/png" sizes="96x96" href="{$favicon_location}favicon-96.png">
    <link rel="icon" type="image/png" sizes="64x64" href="{$favicon_location}favicon-64.png">
    <link rel="icon" type="image/png" sizes="32x32" href="{$favicon_location}favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="{$favicon_location}favicon-16.png">
    <link rel="apple-touch-icon" href="{$favicon_location}favicon-57.png">
    <link rel="apple-touch-icon" sizes="114x114" href="{$favicon_location}favicon-114.png">
    <link rel="apple-touch-icon" sizes="72x72" href="{$favicon_location}favicon-72.png">
    <link rel="apple-touch-icon" sizes="144x144" href="{$favicon_location}favicon-144.png">
    <link rel="apple-touch-icon" sizes="60x60" href="{$favicon_location}favicon-60.png">
    <link rel="apple-touch-icon" sizes="120x120" href="{$favicon_location}favicon-120.png">
    <link rel="apple-touch-icon" sizes="76x76" href="{$favicon_location}favicon-76.png">
    <link rel="apple-touch-icon" sizes="152x152" href="{$favicon_location}favicon-152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="{$favicon_location}favicon-180.png">
    {$debug_toolbar.header}
</head>
<body>
<div id="body-wrapper">
{include file="./menu-top.tpl"}
<div id="content-wrapper" class="container">