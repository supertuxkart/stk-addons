<ul>
    {foreach $items as $item}
        <li>
            {if isset($item.disp)}
                <a class="{$item.class}" href="{$item.disp}">
                    <meta itemprop="realUrl" content="{$item.url}" />
                    {$item.label}
                </a>
            {else}
                <a class="{$item.class}" href="{$item.url}">{$item.label}</a>
            {/if}
        </li>
    {/foreach}
</ul>