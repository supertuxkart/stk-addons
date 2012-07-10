{config_load file="tpl/default/tpl.conf"}
{include file=#header#}

<div id="content">
    <h1>{$about.title}</h1>
    <h2>{$about.stk.title}</h2>
    {foreach $about.stk.content AS $content}
	<p>{$content}</p>
    {/foreach}
    
    <h2>{$about.tf.title}</h2>
    {foreach $about.tf.content AS $content}
	<p>{$content}</p>
    {/foreach}
    
    <h2>{$about.credits.title}</h2>
    <pre>{$about.credits.content}</pre>
</div>{* #content *}
{include file=#footer#}