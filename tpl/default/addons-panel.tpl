{config_load file="{$smarty.current_dir}/tpl.conf"}
{$form_action="upload.php?type={$addon.type}&amp;name={$addon.name}"}
<div itemscope itemtype="http://www.schema.org/CreativeWork">
    <h1>
        <span itemprop="name">{$addon.name}</span>

        <div id="rating-container" itemprop="aggregateRating" itemscope itemtype="http://www.schema.org/AggregateRating">
            <meta itemprop="worstRating" content="{$addon.rating.min_rating}" />
            <meta itemprop="bestRating" content="{$addon.rating.max_rating}" />
            <meta itemprop="ratingValue" content="{$addon.rating.decimal}" />
            <meta itemprop="ratingCount" content="{$addon.rating.count}" />
            <div class="rating">
                <div class="emptystars"></div>
                <div class="fullstars" style="width: {$addon.rating.percent}%;"></div>
            </div>
            <p>{$addon.rating.label}</p>
        </div>
    </h1>

    <div id="addon-image">
        {if $addon.image.display == true}
            <img class="preview" src="{$addon.image.url}" itemprop="image" />
        {/if}
        {if $addon.image_upload}
            <br />
            <form method="POST" action="{$form_action}&amp;action=file">
                <input type="submit" value="{t}Upload Image{/t}" />
            </form>
        {/if}
    </div>

    {$addon.badges}
    <br />
    <span id="addon-description" itemprop="description">{$addon.description}</span>
    <table class="info">
        {if $addon.type == 'arenas'}
            <tr>
                <td><strong>{t}Type:{/t}</strong></td>
                <td>{t}Arena{/t}</td>
            </tr>
        {/if}
        <tr>
            <td><strong>{t}Designer:{/t}</strong></td>
            <td itemprop="author">{$addon.designer}</td>
        </tr>
        <tr>
            <td><strong>{t}Upload date:{/t}</strong></td>
            <td itemprop="dateModified">{$addon.info.upload_date}</td>
        </tr>
        <tr>
            <td><strong>{t}Submitted by:{/t}</strong></td>
            <td><a href="users.php?user={$addon.info.submitter}">{$addon.info.submitter}</a></td>
        </tr>
        <tr>
            <td><strong>{t}Revision:{/t}</strong></td>
            <td itemprop="version">{$addon.info.revision}</td>
        </tr>
        <tr>
            <td><strong>{t}Compatible with:{/t}</strong></td>
            <td>{$addon.info.compatibility}</td>
        </tr>
        {if $addon.vote.display == true}
            <tr>
                <td><strong>{t}Your Rating:{/t}</strong></td>
                <td>{$addon.vote.controls}</td>
            </tr>
        {/if}
    </table>
</div>

{include file="feedback/warnings.tpl"}

{if $addon.dl.display == true}
    <br>
    <br>
    {t}Download this add-on in game!{/t}
{/if}

<h3>{t}License{/t}</h3>
<textarea name="license" rows="4" cols="60" readonly>{$addon.license}</textarea>

<h3>{t}Permalink{/t}</h3>
<a href="{$addon.info.link}">{$addon.info.link}</a>

<h3>{t}Revisions{/t}</h3>
{if $addon.revision_list.upload}
    <div class="pull-right">
        <form method="POST" action="{$form_action}">
            <input type="submit" value="{t}Upload Revision{/t}" />
        </form>
    </div>
{/if}
{foreach $addon.revision_list.revisions as $revision}
    <p>
        {$revision.timestamp}
        <a href="{$revision.file.path}" rel="nofollow">{$revision.dl_label}</a>
    </p>
{/foreach}

<h3>{t}Images{/t}</h3>
{if $addon.image_list.upload}
    <div class="pull-right">
        <form method="POST" action="{$form_action}&amp;action=file">
            <input type="submit" value="{t}Upload Image{/t}">
        </form>
    </div>
{/if}
<div class="image_thumbs">
    {foreach $addon.image_list.images as $image}
        {if $image.approved == 1}
            {$class="image_thumb_container"}
        {else}
            {$class="image_thumb_container unapproved"}
        {/if}
        <div class="{$class}">
            <a href="{$image.url}" target="_blank">
                <img src="{$image.thumb.url}">
            </a>
            <br>
            {$image.admin_links}
        </div>
    {/foreach}
</div>
{if $image@total == 0}
    <p>{t}No images have been uploaded for this addon yet.{/t}</p>
{/if}

<h3>{t}Source Files{/t}</h3>
{if $addon.source_list.upload == true}
    <div class="pull-right">
        <form method="POST" action="{$form_action}&amp;action=file">
            <input type="submit" value="{t}Upload Source File{/t}">
        </form>
    </div>
{/if}
<table>
    {foreach $addon.source_list.files AS $file}
        <tr>
            <td><strong>{$file.label}</strong></td>
            <td>{$file.details}</td>
        </tr>
    {/foreach}
    {if $file@total == 0}
        <tr>
            <td>{t}No source files have been uploaded for this addon yet.{/t}</td>
        </tr>
    {/if}
</table>