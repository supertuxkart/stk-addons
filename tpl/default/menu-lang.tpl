<a href="#">{$lang_menu_lbl}</a>
<ul class="menu-body">
    {foreach $lang_menu_items as $item}
        <li class="flag"><a href="{$item.0}" style="background-position: 0 {$item.1}px;"></a></li>
    {/foreach}
    <li class="label"><a href="https://translations.launchpad.net/stk/stkaddons">Translate STK-Addons</a></li>
</ul>
