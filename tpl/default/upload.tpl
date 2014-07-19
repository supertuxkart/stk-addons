{config_load file="{$smarty.current_dir}/tpl.conf"}
{include file=#header#}

<div id="content">
    {include file=#feedback_errors#}

    {if $upload.display == true}
        {if $upload.form.update == true}
            <form id="formKart" enctype="multipart/form-data" action="upload.php?type={$smarty.get.type}&amp;
            name={$smarty.get.name}&amp;action=submit" method="POST">
            {if $smarty.get.action != 'file'}
                <p>{t}Please upload a new revision of your kart or track.{/t}</p>
            {else}
                <p>{t}What type of file are you uploading?{/t}</p>
                <select name="upload-type" id="upload-type">
                    <option value="source">{t}Source Archive{/t}</option>
                    <option value="image">{t}Image File{/t} (.png, .jpg, .jpeg)</option>
                </select>
                <br>
            {/if}

        {else}
            <form id="formKart" enctype="multipart/form-data" action="upload.php?action=submit" method="POST">
            <p>{t}Please upload a kart or track.{/t}</p>
            <p>{t}Do not use this form if you are updating an existing add-on.{/t}</p>
        {/if}

        <label>{t}File:{/t}<br>
            <input type="file" name="file_addon"><br>
        </label>
        {t}Supported archive types are:{/t} .zip, .tar, .tgz, .tar.gz
        <!-- , .tbz, .tar.bz2 -->
        <br><br>
        <strong>{t}Agreement:{/t}</strong>
        <br>
        <table width="800" id="upload_agreement">
            <tr>
                <td width="1">
                    <input type="radio" name="l_author" id="l_author1" value="1" checked />
                </td>
                <td colspan="3">{t}I am the sole author of every file (model, texture, sound effect, etc.) in this package{/t}</td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td>
                    <input type="checkbox" name="l_licensefile1" id="l_licensefile1">
                </td>
                <td>
                <span id="l_licensetext1">
                    {t}I have included a License.txt file describing the license under which my work is released, and my name (or nickname) if I want credit.{/t}
                    <strong>{t}Required{/t}</strong>
                </span>
                </td>
            </tr>
            <tr>
                <td width="1">
                    <input type="radio" name="l_author" id="l_author2" value="2">
                </td>
                <td colspan="3">{t}I have included open content made by people other than me{/t}</td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="l_licensefile2" id="l_licensefile2"></td>
                <td>
                    <span id="l_licensetext2">
                        {t}I have included a License.txt file including the name of every author whose material is used in this package, along with the license under which their work is released.{/t}
                        <strong>{t}Required{/t}</strong>
                    </span>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    {t}This package includes files released under:{/t}
                    <strong>{t}Must check at least one{/t}</strong>
                </td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_gpl"></td>
                <td>{t}GNU GPL{/t}</td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_cc-by"></td>
                <td>{t}Creative Commons BY 3.0{/t}</td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_cc-by-sa"></td>
                <td>{t}Creative Commons BY SA 3.0{/t}</td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_pd"></td>
                <td>{t}CC0 (Public Domain){/t}</td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_bsd"></td>
                <td>{t}BSD License{/t}</td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_other"></td>
                <td>{t}Other open license{/t}</td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td colspan="2">
                    {t}Files released under other licenses will be rejected unless it can be verified that the license is open.{/t}
                    <br><br>
                </td>
            </tr>
            <tr>
                <td><input type="checkbox" name="l_agreement"></td>
                <td colspan="3">
                    {t}I recognize that if my file does not meet the above rules, it may be removed at any time without prior notice; I also assume the entire responsibility for any copyright violation that may result from not following the above rules.{/t}
                    <strong>{t}Required{/t}</strong>
                    <br><br>
                </td>
            </tr>
            <tr>
                <td><input type="checkbox" name="l_clean"></td>
                <td colspan="3">
                    {t}My package does not include:{/t}<br>
                    <ol>
                        <li>{t}Profanity{/t}</li>
                        <li>{t}Explicit images{/t}</li>
                        <li>{t}Hateful messages and/or images{/t}</li>
                        <li>{t}Any other content that may be unsuitable for children{/t}</li>
                    </ol>
                    <strong>{t}Required{/t}</strong>
                </td>
            </tr>
        </table>
        <input type="submit" class="btn btn-primary" value="{t}Upload file{/t}">
        </form>
    {/if}
</div>

{include file=#footer#}