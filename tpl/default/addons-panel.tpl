{config_load file="{$smarty.current_dir}/tpl.conf"}
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
        {if $addon.image_upload.display == true}
            <br />
            <form method="POST" action="{$addon.image_upload.target}">
                <input type="submit" value="{$addon.image_upload.button_label}" />
            </form>
        {/if}
    </div>

    {$addon.badges}
    <br />
    <span id="addon-description" itemprop="description">{$addon.description}</span>
    <table class="info">
        {if $addon.type == 'arenas'}
            <tr>
                <td><strong>{$addon.info.type.label}</strong></td>
                <td>{$addon.info.type.value}</td>
            </tr>
        {/if}
        <tr>
            <td><strong>{$addon.info.designer.label}</strong></td>
            <td itemprop="author">{$addon.info.designer.value}</td>
        </tr>
        <tr>
            <td><strong>{$addon.info.upload_date.label}</strong></td>
            <td itemprop="dateModified">{$addon.info.upload_date.value}</td>
        </tr>
        <tr>
            <td><strong>{$addon.info.submitter.label}</strong></td>
            <td>{$addon.info.submitter.value}</td>
        </tr>
        <tr>
            <td><strong>{$addon.info.revision.label}</strong></td>
            <td itemprop="version">{$addon.info.revision.value}</td>
        </tr>
        <tr>
            <td><strong>{$addon.info.compatibility.label}</strong></td>
            <td>{$addon.info.compatibility.value}</td>
        </tr>
        {if $addon.vote.display == true}
            <tr>
                <td><strong>{$addon.vote.label}</strong></td>
                <td>{$addon.vote.controls}</td>
            </tr>
        {/if}
    </table>
</div>

{$addon.warnings}

{if $addon.dl.display == true}
    <br />
    <br />
    {$addon.dl.use_client_message}
{/if}

<h3>{$addon.info.license.label}</h3>
<textarea name="license" rows="4" cols="60" readonly>{$addon.info.license.value}</textarea>

<h3>{$addon.info.link.label}</h3>
<a href="{$addon.info.link.value}">{$addon.info.link.value}</a>

<h3>{$addon.revision_list.label}</h3>
{if $addon.revision_list.upload.display == true}
    <div class="pull-right">
        <form method="POST" action="{$addon.revision_list.upload.target}">
            <input type="submit" value="{$addon.revision_list.upload.button_label}" />
        </form>
    </div>
{/if}
<table>
    {foreach $addon.revision_list.revisions as $revision}
        <tr>
            <td>{$revision.timestamp}</td>
            <td><a href="{$revision.file.path}" rel="nofollow">{$revision.dl_label}</a></td>
        </tr>
    {/foreach}
</table>

<h3>{$addon.image_list.label}</h3>
{if $addon.image_list.upload.display == true}
    <div class="pull-right">
        <form method="POST" action="{$addon.image_list.upload.target}">
            <input type="submit" value="{$addon.image_list.upload.button_label}">
        </form>
    </div>
{/if}
<div class="image_thumbs">
    {foreach $addon.image_list.images AS $image}
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
    <p>{$addon.image_list.no_images_message}</p>
{/if}

<h3>{$addon.source_list.label}</h3>
{if $addon.source_list.upload.display == true}
    <div class="pull-right">
        <form method="POST" action="{$addon.source_list.upload.target}">
            <input type="submit" value="{$addon.source_list.upload.button_label}">
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
            <td>{$addon.source_list.no_files_message}</td>
        </tr>
    {/if}
</table>