<div id="lang-menu">
    <a class="menu_head" href="#">{$lang_menu_lbl}</a>
    <ul class="menu_body">
	{foreach $lang_menu_items as $item}
	<li class="flag"><a href="{$item.0}" style="background-position: {$item.1}px {$item.2}px;">{$item.3}</a></li>
	{/foreach}
	<li class="label"><a href="https://translations.launchpad.net/stk/stkaddons">Translate<br />STK-Addons</a></li>
    </ul>
</div>