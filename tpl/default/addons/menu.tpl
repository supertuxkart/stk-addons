{if empty($addons)}
    <p class="text-info">{t}No addons{/t}</p>
{else}
    <div class="list-group">
        {if !isset($current_id)}
            {$current_id=""}
        {/if}
        {foreach $addons as $addon}
            {$active=""}
            {if $addon.id === $current_id}
                {$active=" active"}
            {/if}
            <a href="{$addon.disp}" class="list-group-item addon-list{$addon.class}{$active}">
                <meta itemprop="realUrl" content="{$addon.real_url}" />
                {if $addon.is_featured}
                    <div class="icon-featured"></div>
                {/if}
                <img class="icon" src="{$addon.image_src}" height="25" width="25">
                <span>{$addon.name|truncate:28}</span>
            </a>
        {/foreach}
    </div>
{/if}
{$pagination}