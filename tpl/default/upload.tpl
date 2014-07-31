{config_load file="{$smarty.current_dir}/tpl.conf"}
{include file=#header#}
<div id="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class="text-center">Upload</h1><hr>
        </div>
    </div>
    {include file="feedback/errors.tpl"}
    {include file="feedback/warnings.tpl"}
    {include file="feedback/success.tpl"}
    {if $upload.display}
        {if $upload.form.update}
            <form id="formKart" enctype="multipart/form-data" class="form-horizontal" action="upload.php?type={$upload.type}&amp;name={$upload.name}&amp;action=submit" method="POST">
            {if $upload.action != 'file'}
                <p class="alert-info alert">{t}Please upload a new revision of your kart or track.{/t}</p>
            {else}
                <div class="form-group">
                    <label class="col-md-2">
                        {t}What type of file are you uploading?{/t}
                    </label>
                    <div class="col-md-10">
                        <select name="upload-type" id="upload-type">
                            <option value="source">{t}Source Archive{/t}</option>
                            <option value="image">{t}Image File{/t} (.png, .jpg, .jpeg)</option>
                        </select>
                    </div>
                </div>
            {/if}
        {else}
            <p class="alert alert-warning">{t}Do not use this form if you are updating an existing add-on.{/t}</p>
            <p class="alert alert-info">{t}Please upload a kart or track(arena).{/t}</p>
            <form id="formKart" enctype="multipart/form-data" class="form-horizontal" action="upload.php?action=submit" method="POST">
        {/if}

        <div class="form-group">
            <label for="file_addon" class="col-md-2">
                {t}File:{/t}
            </label>
            <div class="col-md-10">
                <input type="file" id="file_addon" name="file_addon"><br>
            </div>
        </div>
        <div class="form-group">
            <div class="col-md-offset-2 col-md-10">
                <span>{t}Supported archive types are:{/t} .zip, .tar, .tgz, .tar.gz .tbz, .tar.bz2</span>
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-2">
                {t}Agreement:{/t}
            </label>
            <div class="col-md-10">
                <div class="checkbox">
                    <label>
                        <input type="radio" name="l_author" id="l_author1" value="1" checked>
                        {t}I am the sole author of every file (model, texture, sound effect, etc.) in this package{/t}
                    </label>
                </div>
                <div class="col-md-offset-1 checkbox">
                    <label>
                        <input type="checkbox" name="l_licensefile1" id="l_licensefile1">
                        <span id="l_licensetext1">
                            {t}I have included a License.txt file describing the license under which my work is released, and my name (or nickname) if I want credit.{/t}
                            <strong>{t}Required{/t}</strong>
                        </span>
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="radio" name="l_author" id="l_author2" value="2">
                        {t}I have included open content made by people other than me{/t}
                    </label>
                </div>
                <div class="checkbox col-md-offset-1">
                    <label>
                        <input type="checkbox" name="l_licensefile2" id="l_licensefile2">
                        <span id="l_licensetext2">
                            {t}I have included a License.txt file including the name of every author whose material is used in this package, along with the license under which their work is released.{/t}
                            <strong>{t}Required{/t}</strong>
                        </span>
                    </label>
                </div><hr>
                {t}This package includes files released under:{/t}
                <strong>{t}Must check at least one{/t}</strong>
                <div class="col-md-offset-1">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="license_gpl">{t}GNU GPL{/t}
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="license_cc-by">{t}Creative Commons BY 3.0{/t}
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="license_cc-by-sa">{t}Creative Commons BY SA 3.0{/t}
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="license_pd">{t}CC0 (Public Domain){/t}
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="license_bsd">{t}BSD License{/t}
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="license_other">{t}Other open license{/t}
                        </label>
                    </div><br>
                    <span class="label label-info">
                        {t}Files released under other licenses will be rejected unless it can be verified that the license is open.{/t}
                    </span>
                </div><hr>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="l_agreement">
                        {t}I recognize that if my file does not meet the above rules, it may be removed at any time without prior notice; I also assume the entire responsibility for any copyright violation that may result from not following the above rules.{/t}
                        <strong>{t}Required{/t}</strong>
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="l_clean">
                        {t}My package does not include:{/t} <strong>{t}Required{/t}</strong>
                        <ol>
                            <li>{t}Profanity{/t}</li>
                            <li>{t}Explicit images{/t}</li>
                            <li>{t}Hateful messages and/or images{/t}</li>
                            <li>{t}Any other content that may be unsuitable for children{/t}</li>
                        </ol>
                    </label>
                </div>
            </div>
            <hr>
            <div class="form-group">
                <div class="col-md-offset-2 col-sm-offset-0 col-sm-1 col-md-2">
                    <input type="submit" class="btn btn-success btn-block" value="{t}Upload file{/t}">
                </div>
            </div>
        </div>
        </form>
    {/if}
</div>
{include file=#footer#}