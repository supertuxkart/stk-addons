<h1>{t}Overview{/t}</h1>
<h2>{t}Unapproved Add-Ons{/t}</h2>
<p>{t}Note that only add-ons where the newest revision is unapproved will appear here.{/t}</p>
{if !empty($overview.addons)}
    {foreach $overview.addons as $addon}
        <strong><a href="{$addon.href}">{$addon.name}</a></strong><br>
        {t}Revisions:{/t} {$addon.unapproved}
        <br><br>
    {/foreach}
{else}
    <p>{t}No unapproved add-ons.{/t}</p><br>
{/if}

<h2>{t}Unapproved Files{/t}</h2>
<h3>{t}Images:{/t}</h3>
{if !empty($overview.images)}
    {foreach $overview.images as $image}
        <strong><a href="{$image.href}">{$image.name}</a></strong><br>
        {t}Images:{/t} {$image.unapproved}
        <br><br>
    {/foreach}
{else}
    <p>{t}No unapproved images.{/t}</p><br>
{/if}

<h3>{t}Source Archives:{/t}</h3>
{if !empty($overview.archives)}
    {foreach $overview.archives as $archive}
        <strong><a href="{$archive.href}">{$archive.name}</a></strong><br>
        {t count=$archive.unapproved|@count 1=$archive.unapproved plural="%1 Files"}%1 File{/t}
        <br><br>
    {/foreach}
{else}
    <p>{t}No unapproved source archives.{/t}</p><br>
{/if}

<button type="button" id="reset-ranking-btn" class="btn btn-danger">Reset Player Ranking</button>
