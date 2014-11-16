{include file=$tpl_config.header}
<div id="index-body" class="stk-image">
    <img id="index-logo" src="{$smarty.const.IMG_LOCATION}logo.png" alt="SuperTuxKart Logo" title="SuperTuxKart Logo" />
    
    <div id="index-menu">
	{foreach $index_menu as $index}
	    <div>
		<a href="{$index.href}" class="{$index.type}" itemscope itemtype="http://schema.org/SiteNavigationElement">
		    <span itemprop="text">{$index.label}</span>
		    <meta itemprop="url" content="{$index.href}" />
		</a>
	    </div>
	{/foreach}
    </div>{* #index-menu *}
    <div id="index-news">
	<noscript><div style="display: none;"></noscript>
	<ul id="news-messages">
	    {foreach $news_messages as $message}
		<li>{$message}</li>
	    {/foreach}
	</ul>
	<noscript></div></noscript>
    </div>
</div>{* #index-body *}
{include file=$tpl_config.footer}