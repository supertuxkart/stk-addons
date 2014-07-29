{if empty($addons)}
    <p class="text-info">{t}No addons{/t}</p>
{else}
    <ul class="list-group">
        {foreach $addons as $addon}
            <li class="list-group-item">
                <a href="{$addon.disp}" class="addon-list{$addon.class}">
                    <meta itemprop="realUrl" content="{$addon.real_url}" />
                    {if $addon.is_featured}
                        <div class="icon-featured"></div>
                    {/if}
                    <div class="icon">
                        <img class="icon" src="{$addon.image_src}" height="25" width="25">
                    </div>
                    <span>{$addon.name|escape}</span>
                </a>
            </li>
        {/foreach}
    </ul>
{/if}
{$pagination}