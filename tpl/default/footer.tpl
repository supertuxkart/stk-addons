</div>{* #main-frame *}
<div id="footer" itemscope itemtype="http://schema.org/WPFooter">Site hosted by <a href="http://www.tuxfamily.org/">tuxfamily.org</a> | {$menu.about}</div>
{foreach $script_inline.before as $script}
    <script type="{$script.type|default:'text/javascript'}">{$script.content}</script>
{/foreach}
{foreach $script_includes as $script}
    <script type="{$script.type|default:'text/javascript'}" src="{$script.src}"></script>
{/foreach}
{foreach $script_inline.after as $script}
    <script type="{$script.type|default:'text/javascript'}">{$script.content}</script>
{/foreach}
</body>
</html>