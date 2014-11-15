{if empty($addons)}
    <p class="text-info">{t}No addons{/t}</p>
{else}
    <div class="list-group">
        {foreach $addons as $addon}
            <a href="{$addon.disp}" class="list-group-item addon-list{$addon.class}">
                <meta itemprop="realUrl" content="{$addon.real_url}" />
                {if $addon.is_featured}
                    <div class="icon-featured"></div>
                {/if}
                <img class="icon" src="{$addon.image_src}" height="25" width="25">
                <span>{$addon.name|escape}</span>
            </a>
        {/foreach}
    </div>
{/if}
{$pagination}