<a href="#">{$lang_menu_lbl}</a>
<ul class="menu-body">
    {foreach $lang_menu_items as $item}
        <li class="flag"><a href="{$item.0}" style="background-position: 0px {$item.1}px;">{$item.2}</a></li>
    {/foreach}
    <li class="label"><a href="https://translations.launchpad.net/stk/stkaddons">Translate<br>STK-Addons</a></li>
</ul>
