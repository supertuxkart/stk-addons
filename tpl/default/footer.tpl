</div>{* content-wrapper *}
<footer itemscope itemtype="http://schema.org/WPFooter">
    {t}Site hosted by {/t}<a href="http://www.tuxfamily.org/">tuxfamily.org</a> |
    <a href="{$menu.about}">{t}About{/t}</a> | <a href="{$menu.privacy}">{t}Privacy{/t}</a>
</footer>
</div> {* #body-wrapper *}
{foreach $script_inline.before as $script}
    <script type="{$script.type|default:'text/javascript'}">{$script.content}</script>
{/foreach}
{foreach $script_includes as $script}
    {if isset($script.ie) && $script.ie}
<!--[if lte IE 8]><<script language="javascript" type="{$script.type|default:'text/javascript'}" src="{$script.src}"></script><![endif]-->
    {else}
        <script type="{$script.type|default:'text/javascript'}" src="{$script.src}"></script>
    {/if}
{/foreach}
{foreach $script_inline.after as $script}
    <script type="{$script.type|default:'text/javascript'}">{$script.content}</script>
{/foreach}
</body>
</html>