<a href="#">{$lang.label}</a>
<ul class="menu-body">
    {foreach $lang.items as $item}
        <li class="flag"><a href="{$item.url}" style="background-position: 0 {$item.y}px;"></a></li>
    {/foreach}
    <li class="label"><a href="https://www.transifex.com/supertuxkart/supertuxkart/">{t}Translate STK-Addons{/t}</a></li>
</ul>
