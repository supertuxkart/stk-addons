</div>{* content-wrapper *}
<div id="footer" itemscope itemtype="http://schema.org/WPFooter">{t}Site hosted by {/t}<a href="http://www.tuxfamily.org/">tuxfamily.org</a> | {$menu.about}</div>
</div> {* #body-wrapper *}
{foreach $script_inline.before as $script}
    <script type="{$script.type|default:'text/javascript'}">{$script.content}</script>
{/foreach}
{foreach $script_includes as $script}
    {if isset($script.ie) && $script.ie}
<!--[if lte IE 8]><<script language="JavaScript" type="{$script.type|default:'text/javascript'}" src="{$script.src}"></script><![endif]-->
    {else}
        <script type="{$script.type|default:'text/javascript'}" src="{$script.src}"></script>
    {/if}
{/foreach}
{foreach $script_inline.after as $script}
    <script type="{$script.type|default:'text/javascript'}">{$script.content}</script>
{/foreach}
</body>
</html>