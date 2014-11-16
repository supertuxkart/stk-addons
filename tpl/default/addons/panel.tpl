{$upload_location="upload.php?type={$addon.type}&amp;name={$addon.name}"}
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

    <div class="row">
        <div class="col-md-6">
            {$addon.badges}
            <span id="addon-description" itemprop="description">{$addon.description}</span>
            <table class="table">
                {if $addon.is_arena}
                    <tr>
                        <td><strong>{t}Type{/t}</strong></td>
                        <td>{t}Arena{/t}</td>
                    </tr>
                {/if}
                <tr>
                    <td><strong>{t}Designer{/t}</strong></td>
                    <td itemprop="author">{$addon.designer}</td>
                </tr>
                <tr>
                    <td><strong>{t}Upload date:{/t}</strong></td>
                    <td itemprop="dateModified">{$addon.info.upload_date}</td>
                </tr>
                <tr>
                    <td><strong>{t}Submitted by{/t}</strong></td>
                    <td><a href="users.php?user={$addon.info.submitter}">{$addon.info.submitter}</a></td>
                </tr>
                <tr>
                    <td><strong>{t}Revision:{/t}</strong></td>
                    <td itemprop="version">{$addon.info.revision}</td>
                </tr>
                <tr>
                    <td><strong>{t}Compatible with{/t}</strong></td>
                    <td>{$addon.info.compatibility}</td>
                </tr>
                {if $is_logged}
                    <tr>
                        <td><strong>{t}Your Rating{/t}</strong></td>
                        <td>{$addon.vote}</td>
                    </tr>
                {/if}
            </table>
        </div>
        <div class="col-md-6">
            <div class="text-center">
                {if $addon.image_url}
                    <img class="preview" src="{$addon.image_url}" itemprop="image"><br>
                {/if}
                {if $can_edit}
                    <a href="{$upload_location}&amp;action=file" class="btn btn-default">{t}Upload Image{/t}</a>
                {/if}
            </div>
        </div>
    </div>
</div>

{include file="feedback/warnings.tpl"}

{if $addon.dl}
    <br>
    <p>{t}Download this add-on in game!{/t}</p>
{/if}

<h1>{t}License{/t}</h1>
<textarea class="form-control" id="license" name="license" rows="8" cols="60" readonly>{$addon.license}</textarea>

<h3>{t}Permalink{/t}</h3>
<a href="{$addon.info.link}">{$addon.info.link}</a>

<h3>{t}Revisions{/t}</h3>
{if $can_edit}
    <div class="pull-right">
        <a href="{$upload_location}" class="btn btn-default">{t}Upload Revision{/t}</a>
    </div>
{/if}
{foreach $addon.view_revisions as $revision}
    <p>
        {$revision.timestamp}
        <a href="{$revision.file_path}" rel="nofollow">{$revision.dl_label}</a>
    </p>
{/foreach}

<h3>{t}Images{/t}</h3>
{if $can_edit}
    <div class="pull-right">
        <a class="btn btn-default" href="{$upload_location}&amp;action=file">{t}Upload Image{/t}</a>
    </div>
{/if}
<div class="image_thumbs">
    {foreach $addon.images as $image}
        {if $image.approved}
            {$class="image_thumb_container"}
        {else}
            {$class="image_thumb_container unapproved"}
        {/if}
        <div class="{$class}">
            <a href="{$image.url}" target="_blank">
                <img src="{$image.thumb.url}">
            </a>
            <br>
            {if isset($image.unapprove_link)}
                <a href="{$image.unapprove_link}">{t}Unapprove{/t}</a><br>
            {/if}
            {if isset($image.approve_link)}
                <a href="{$image.approve_link}">{t}Approve{/t}</a><br>
            {/if}
            {if isset($image.icon_link)}
                <a href="{$image.icon_link}">{t}Set Icon{/t}</a><br>
            {/if}
            {if isset($image.image_link)}
                <a href="{$image.image_link}">{t}Set Image{/t}</a><br>
            {/if}
            {if isset($image.delete_link)}
                <a href="{$image.delete_link}">{t}Delete File{/t}</a><br>
            {/if}
        </div>
    {/foreach}
</div>
{if $image@total == 0}
    <p>{t}No images have been uploaded for this addon yet.{/t}</p>
{/if}

<h3>{t}Source Files{/t}</h3>
{if $can_edit}
    <div class="pull-right">
        <a href="{$upload_location}&amp;action=file" class="btn btn-default">{t}Upload Source File{/t}</a>
    </div>
{/if}
<table>
    {foreach $addon.sources as $source}
        <tr>
            <td><strong>{$source.label}</strong></td>
            <td>
                {if !$source.approved}
                    ({t}Not Approved{/t})
                {/if}
                <a rel="nofollow" href="{$source.download_link}">{t}Download{/t}</a><br>
                {if isset($source.unapprove_link)}
                    | <a href="{$source.unapprove_link}">{t}Unapprove{/t}</a><br>
                {/if}
                {if isset($source.approve_link)}
                    | <a href="{$source.approve_link}">{t}Approve{/t}</a><br>
                {/if}
                {if isset($source.delete_link)}
                    | <a href="{$source.delete_link}">{t}Delete File{/t}</a><br>
                {/if}
            </td>
        </tr>
    {/foreach}
    {if $source@total == 0}
        <tr>
            <td>{t}No source files have been uploaded for this addon yet.{/t}</td>
        </tr>
    {/if}
</table>

{*Configuration*}
{if $can_edit}
    <h3>Actions</h3>
    <input type="button" class="btn btn-danger" value="{t}Delete Addon{/t}" onClick="confirm_delete('{$addon.config.delete_link}')"><br>
    <hr>
    <h3>{t}Configuration{/t}</h3>
    <form name="changeProps" action="{$addon.config.change_props_action}" method="POST" class="form-horizontal">
        <label for="designer_field">{t}Designer{/t}</label><br>
        <input type="text" name="designer" class="form-control" id="designer_field" value="{$addon.designer}"><br>
        <label for="desc_field">{t}Description{/t} ({t 1=140}Max %1 characters{/t})</label><br>
        <textarea name="description" id="desc_field" class="form-control" rows="4" cols="60">{$addon.description}</textarea><br>
        <input type="submit" class="btn btn-default" value="{t}Save Properties{/t}">
    </form><br>
    {*Mark whether or not an add-on has ever been included in STK*}
    {if $has_permission}
        <h4>{t}Included in Game Versions:{/t}</h4>
        <form method="POST" action="{$addon.config.include_action}" class="form-horizontal">
            <div class="form-group">
                <label for="incl_start" class="col-md-1">
                    {t}Start{/t}
                </label>
                <div class="col-md-3">
                    <input type="text" class="form-control" id="incl_start" name="incl_start" size="6" value="{$addon.min}"><br>
                </div>
            </div>
            <div class="form-group">
                <label for="incl_end" class="col-md-1">
                    {t}End{/t}
                </label>
                <div class="col-md-3">
                    <input type="text" id="incl_end" name="incl_end" class="form-control" size="6" value="{$addon.max}"><br>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-offset-1 col-md-3">
                    <input type="submit" class="btn btn-success" value="{t}Save{/t}"><br>
                </div>
            </div>
        </form><br>
    {/if}

    {*Set status flags*}
    <h4>{t}Status Flags:{/t}</h4>
    <form method="POST" action="{$addon.config.status.action}">
        <table id="addon_flags" class="table table-striped">
            <thead>
                <tr>
                    <th></th>
                    {if $has_permission}
                        <th>{$addon.config.status.approve_img}</th>
                        <th>{$addon.config.status.invisible_img}</th>
                        <th>{$addon.config.status.dfsg_img}</th>
                        <th>{$addon.config.status.featured_img}</th>
                    {/if}
                    <th>{$addon.config.status.alpha_img}</th>
                    <th>{$addon.config.status.beta_img}</th>
                    <th>{$addon.config.status.rc_img}</th>
                    <th>{$addon.config.status.latest_img}</th>
                    <th>{$addon.config.status.invalid_img}</th>
                </tr>
            </thead>
            <tbody>
            {$fields=[]}
            {$fields[]="latest"}
            {foreach $addon.view_revisions as $revision}
                {$rev_n=$revision.number}
                <tr>
                    <td class="text-center">{t 1=$rev_n}Rev %1:{/t}</td>
                    {if $has_permission}
                        {$approve=""} {$invisible=""} {$dfsg=""} {$featured=""}
                        {if $revision.is_approved} {$approve=" checked"} {/if}
                        {if $revision.is_invisible} {$invisible=" checked"} {/if}
                        {if $revision.is_dfsg} {$dfsg=" checked"} {/if}
                        {if $revision.is_featured} {$featured=" checked"} {/if}

                        <td><input type="checkbox" name="approved-{$rev_n}"{$approve}></td>
                        <td><input type="checkbox" name="invisible-{$rev_n}"{$invisible}></td>
                        <td><input type="checkbox" name="dfsg-{$rev_n}"{$dfsg}></td>
                        <td><input type="checkbox" name="featured-{$rev_n}"{$featured}></td>
                        {$fields[] = "approved-$rev_n"} {$fields[] = "invisible-$rev_n"}
                        {$fields[] = "dfsg-$rev_n"} {$fields[] = "featured-$rev_n"}
                    {/if}

                    {$alpha=""} {$beta=""} {$rc=""} {$latest=""} {$invalid=""}
                    {if $revision.is_alpha} {$alpha=" checked"} {/if}
                    {if $revision.is_beta} {$beta=" checked"} {/if}
                    {if $revision.is_rc} {$rc=" checked"} {/if}
                    {if $revision.is_latest} {$latest=" checked"} {/if}
                    {if $revision.is_invalid} {$invalid=" checked"} {/if}

                    <td><input type="checkbox" name="alpha-{$rev_n}"{$alpha}></td>
                    <td><input type="checkbox" name="beta-{$rev_n}"{$beta}></td>
                    <td><input type="checkbox" name="rc-{$rev_n}"{$rc}></td>
                    <td><input type="radio" value="{$rev_n}" name="latest"{$latest}></td>
                    <td><input type="checkbox" disabled name="texpower-{$rev_n}"{$invalid}></td>
                    {$fields[] = "alpha-$rev_n"} {$fields[] = "beta-$rev_n"} {$fields[] = "rc-$rev_n"}

                    {*Delete revision button*}
                    <td><input type="button" class="btn btn-danger" value="{t 1=$rev_n}Delete revision %1{/t}" onclick="confirm_delete('{$revision.delete_link}')"></td>
                </tr>
            {/foreach}
            </tbody>
        </table>
        <input type="hidden" name="fields" value="{','|implode:$fields}">
        <div class="col-md-offset-8">
            <input type="submit" class="btn btn-success btn-block" value="{t}Save Changes{/t}">
        </div>
    </form><br>

    {*Moderator notes*}
    <h4>{t}Notes from Moderator to Submitter:{/t}</h4>
    {if $has_permission}
        <form method="POST" action="{$addon.config.moderator_action}">
    {/if}
    {$fields=[]}
    {foreach $addon.revisions as $revision}
        {$rev_n=$revision@key}
        {t 1=$rev_n}Rev %1:{/t}<br>
        <textarea name="notes-{$rev_n}" class="form-control" rows="6" cols="60">{$revision.moderator_note}</textarea><br>
        {$fields[]="notes-$rev_n"}
    {/foreach}
    {if $has_permission}
         <input type="hidden" name="fields" value="{','|implode:$fields}">
         <input type="submit" class="btn btn-success" value="{t}Save Notes{/t}">
         </form>
    {/if}
{/if}
