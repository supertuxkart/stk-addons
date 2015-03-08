{$upload_location="upload.php?type={$addon.type}&amp;name={$addon.id}"}
<input type="hidden" id="addon-id" value="{$addon.id}">
<div itemscope itemtype="http://www.schema.org/CreativeWork">
    <div class="row">
        <div class="col-md-9 pull-left">
            <h1><span itemprop="name">{$addon.name}</span></h1>
            <p>{$addon.badges}</p>
        </div>
        <div class="pull-right">
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
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-md-6">
            <p id="addon-description" itemprop="description">{$addon.description}</p>
            <table class="table">
                {if $addon.is_arena}
                    <tr>
                        <td><strong>{t}Type{/t}</strong></td>
                        <td>{t}Arena{/t}</td>
                    </tr>
                {/if}
                <tr>
                    <td><strong>{t}Designer{/t}</strong></td>
                    <td itemprop="author" id="addon-designer">{$addon.designer}</td>
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
                    <a href="{$upload_location}&amp;upload-type=image" class="btn btn-default">{t}Upload Image{/t}</a>
                {/if}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        {include file="feedback/warnings.tpl"}
        {if $addon.dl}
            <p>{t}Download this add-on in game!{/t}</p>
        {/if}
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h3>{t}License{/t}</h3>
        <textarea class="form-control" id="addon-license" name="license" rows="8" cols="60" readonly>{$addon.license}</textarea>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h3>{t}Permalink{/t}</h3>
        <a href="{$addon.info.link}">{$addon.info.link}</a>
    </div>
</div>

<h3>{t}Revisions{/t}</h3>
{if $can_edit}
    <div class="pull-right">
        <a href="{$upload_location}&amp;upload-type=revision" class="btn btn-default">{t}Upload Revision{/t}</a>
    </div>
{/if}
{foreach $addon.view_revisions as $revision}
    <p>
        {$revision.timestamp}
        <a href="{$revision.file_path}" rel="nofollow">{$revision.dl_label}</a>
    </p>
{/foreach}

<div class="row">
    <div class="col-md-12">
        <h3>{t}Images{/t}</h3>
        {if $can_edit}
            <div class="pull-right">
                <a class="btn btn-default" href="{$upload_location}&amp;upload-type=image">{t}Upload Image{/t}</a>
            </div>
        {/if}
        {foreach $addon.images as $image}
            {$class_container=""}
            {if $can_edit}
                {if $image.is_approved}
                    {$class_container=" bg-success"}
                {else}
                    {$class_container=" bg-danger"}
                {/if}
            {/if}
            <div class="text-center pull-left addon-images-container{$class_container}">
                <a href="{$image.url}" target="_blank">
                    <img src="{$image.thumb_url}" class="">
                </a><br>
                {if $can_edit}
                    <div class="btn-group-vertical" data-id="{$image.id}">
                        {if $has_permission}
                            {$class_approve=""} {$class_unapprove=""}
                            {if $image.is_approved}
                                {$class_approve=" hidden"}
                            {else}
                                {$class_unapprove=" hidden"}
                            {/if}
                            <button type="button" class="btn btn-link btn-unapprove-file{$class_unapprove}">{t}Unapprove{/t}</button>
                            <button type="button" class="btn btn-link btn-approve-file{$class_approve}">{t}Approve{/t}</button>
                        {/if}
                        {$class_icon=" hidden"} {$class_image=" hidden"}
                        {if $image.has_icon}
                            {$class_icon=""}
                        {/if}
                        {if $image.has_image}
                            {$class_image=""}
                        {/if}
                        <button type="button" class="btn btn-link btn-set-icon{$class_icon}">{t}Set Icon{/t}</button>
                        <button type="button" class="btn btn-link btn-set-image{$class_image}">{t}Set Image{/t}</button>
                        <button type="button" class="btn btn-link btn-delete-file">{t}Delete File{/t}</button>
                    </div>
                {/if}
            </div>
        {/foreach}
        {if $image@total == 0}
            <p>{t}No images have been uploaded for this addon yet.{/t}</p>
        {/if}
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h3>{t}Source Files{/t}</h3>
        {if $can_edit}
            <div class="pull-right">
                <a href="{$upload_location}&amp;upload-type=source" class="btn btn-default">{t}Upload Source File{/t}</a>
            </div>
        {/if}
            {foreach $addon.sources as $source}
                {$class_container=""}
                {if $can_edit}
                    {if $source.is_approved}
                        {$class_container=" bg-success"}
                    {else}
                        {$class_container=" bg-danger"}
                    {/if}
                {/if}
                <div class="col-md-8{$class_container}">
                    <strong>{$source.label}</strong>
                    <div class="btn-group" data-id="{$source.id}">
                        <a rel="nofollow" class="btn btn-link" href="{$source.url}">{t}Download{/t}</a>
                        {if $can_edit}
                            {if $has_permission}
                                {$class_approve=""} {$class_unapprove=""}
                                {if $source.is_approved}
                                    {$class_approve=" hidden"}
                                {else}
                                    {$class_unapprove=" hidden"}
                                {/if}
                                <button type="button" class="btn btn-link btn-unapprove-file{$class_unapprove}">{t}Unapprove{/t}</button>
                                <button type="button" class="btn btn-link btn-approve-file{$class_approve}">{t}Approve{/t}</button>
                            {/if}
                            <button type="button" class="btn btn-link btn-delete-file">{t}Delete File{/t}</button>
                        {/if}
                    </div>
                </div>
            {/foreach}
            {if $source@total == 0}
                <p>{t}No source files have been uploaded for this addon yet.{/t}</p>
            {/if}
    </div>
</div>

{*Configuration*}
{if $can_edit}
    <hr>
    <div class="row">
        <div class="col-md-12">
            <h3>Actions</h3>
            <button id="btn-delete-addon" class="btn btn-danger">{t}Delete Addon{/t}</button><br>
        </div>
    </div><hr>
    <div class="row">
        <div class="col-md-12">
            <h3>{t}Configuration{/t}</h3>
            <form id="addon-edit-props" class="form-horizontal">
                <div class="form-group">
                    <label for="addon-edit-designer" class="col-md-12">{t}Designer{/t}</label>
                    <div class="col-md-12">
                        <input type="text" name="designer" class="form-control" id="addon-edit-designer" value="{$addon.designer}">
                    </div>
                </div>
                <div class="form-group">
                    <label for="addon-edit-description" class="col-md-12">{t}Description{/t} ({t 1=140}Max %1 characters{/t})</label><br>
                    <div class="col-md-12">
                        <textarea name="description" id="addon-edit-description" class="form-control" rows="4" cols="60">{$addon.description}</textarea>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-offset-8">
                        <input type="hidden" name="action" value="edit-props">
                        <input type="hidden" name="addon-id" value="{$addon.id}">
                        <input type="submit" class="btn btn-success btn-block" value="{t}Save Properties{/t}">
                    </div>
                </div>
            </form>
        </div>
    </div><hr>
    {*Mark whether or not an add-on has ever been included in STK*}
    {if $has_permission}
    <div class="row">
        <div class="col-md-12">
            <h4>{t}Included in Game Versions:{/t}</h4>
            <form id="addon-edit-include-versions" class="form-horizontal">
                <div class="form-group">
                    <label for="addon-edit-include-start" class="col-md-1">
                        {t}Start{/t}
                    </label>
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="addon-edit-include-start"
                               name="include-start" value="{$addon.min}" placeholder="W.X.Y[-rcZ]">
                    </div>
                </div>
                <div class="form-group">
                    <label for="addon-edit-include-end" class="col-md-1">
                        {t}End{/t}
                    </label>
                    <div class="col-md-3">
                        <input type="text" id="addon-edit-include-end" class="form-control"
                               name="include-end"  value="{$addon.max}" placeholder="W.X.Y[-rcZ]">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-offset-1 col-md-3">
                        <input type="hidden" name="action" value="edit-include-versions">
                        <input type="hidden" name="addon-id" value="{$addon.id}">
                        <input type="submit" class="btn btn-success" value="{t}Save{/t}">
                    </div>
                </div>
            </form>
        </div>
    </div><hr>
    {/if}
    {*Set status flags*}
    <div class="row">
        <div class="col-md-12">
            <h4>{t}Status Flags:{/t}</h4>
            <form id="addon-set-flags">
                <table class="table table-striped">
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
                            <td><button type="button" class="btn btn-danger btn-delete-revision" data-id="{$rev_n}">{t 1=$rev_n}Delete revision %1{/t}</button></td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
                <input type="hidden" name="fields" value="{','|implode:$fields}">
                <div class="col-md-offset-8">
                    <input type="hidden" name="action" value="set-flags">
                    <input type="hidden" name="addon-id" value="{$addon.id}">
                    <input type="submit" class="btn btn-success btn-block" value="{t}Save Changes{/t}">
                </div>
            </form>
        </div>
    </div><hr>
    {*Moderator notes*}
    <div class="row">
        <div class="col-md-12">
            <h4>{t}Notes from Moderator to Submitter:{/t}</h4>
            {$readonly=" readonly"}
            {if $has_permission}
                {$readonly=""}
                <form id="addon-set-notes">
            {/if}
            {$fields=[]}
            {foreach $addon.revisions as $revision}
                {$rev_n=$revision@key}
                <div class="row">
                    <label class="col-md-12" for="note-{$rev_n}">{t 1=$rev_n}Rev %1:{/t}<label>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <textarea name="note-{$rev_n}" id="note-{$rev_n}" class="form-control" rows="6" cols="60"{$readonly}>{$revision.moderator_note}</textarea><br>
                    </div>
                </div>
                {$fields[]="$rev_n"}
            {/foreach}
            {if $has_permission}
                    <div class="col-md-offset-8">
                        <input type="hidden" name="fields" value="{','|implode:$fields}">
                        <input type="hidden" name="action" value="set-notes">
                        <input type="hidden" name="addon-id" value="{$addon.id}">
                        <input type="submit" class="btn btn-success btn-block" value="{t}Save Notes{/t}">
                    </div>
                </form>
            {/if}
        </div>
    </div>
{/if}
