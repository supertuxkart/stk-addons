{config_load file="tpl/default/tpl.conf"}
{include file=#header#}

<div id="index-body">
    <img id="index-logo"
	src="{#tpl_image_dir#}logo.png"
	alt="SuperTuxKart Logo"
	title="SuperTuxKart Logo" />
    
    <div id="index-menu">
	{foreach $index_menu as $index}
	    <div>
		<a href="{$index.href}" class="{$index.type}">
		    <span>{$index.label}</span>
		</a>
	    </div>
	{/foreach}
    </div>{* #index-menu *}
    <div id="index-news">
	<ul id="news-messages">
	    {foreach $news_messages as $message}
		<li>{$message}</li>
	    {/foreach}
	</ul>
    </div>
</div>{* #index-body *}
{include file=#footer#}