{config_load file="tpl/default/tpl.conf"}
{include file=#header#}

<div id="content">
    {include file=#feedback_errors#}

    {if $upload.display == true}
        {if $upload.form.update == true}
            <form id="formKart" enctype="multipart/form-data" action="upload.php?type={$smarty.get.type}&
            name={$smarty.get.name}&amp;action=submit" method="POST">
            {if $smarty.get.action != 'file'}
                {$upload.form.addon.new_revision}<br>
            {else}
                {$upload.form.addon.type}<br>
                <select name="upload-type" id="upload-type" onChange="uploadFormFieldToggle();">
                    <option value="source">{$upload.form.addon.source_file}</option>
                    <option value="image">'{$upload.form.addon.image_file} (.png, .jpg, .jpeg)</option>
                </select>
                <br>
            {/if}

        {else}
            <form id="formKart" enctype="multipart/form-data" action="upload.php?action=submit" method="POST">
            {$upload.form.addon.kart_or_track}<br>
            {$upload.form.addon.existing_warn}<br>
        {/if}

        <label>{$upload.form.file_button}<br />
            <input type="file" name="file_addon" /><br />
        </label>
        {$upload.form.supported_arh}.zip, .tar, .tgz, .tar.gz
        <!-- , .tbz, .tar.bz2 -->
        <br />
        <br />
        <strong>{$upload.form.agreement_text}</strong>
        <br>
        <table width="800" id="upload_agreement">
            <tr>
                <td width="1">
                    <input type="radio" name="l_author" id="l_author1" value="1"
                           onChange="uploadFormFieldToggle();" checked />
                </td>
                <td colspan="3">
                    {$upload.form.author_self.label}
                </td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td>
                    <input type="checkbox" name="l_licensefile1" id="l_licensefile1">
                </td>
                <td>
                <span id="l_licensetext1">
                    {$upload.form.author_self.license} <strong>{$upload.form.required_text}</strong>
                </span>
                </td>
            </tr>
            <tr>
                <td width="1">
                    <input type="radio" name="l_author" id="l_author2" value="2" onChange="uploadFormFieldToggle();" />
                </td>
                <td colspan="3">
                    {$upload.form.author_other.label}
                </td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="l_licensefile2" id="l_licensefile2"></td>
                <td>
                    <span id="l_licensetext2">{$upload.form.author_other.license}
                        <strong>{$upload.form.required_text}</strong></span>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    {$upload.form.license.label} <strong>{$upload.form.license.instructions}</strong>
                </td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_gpl" /></td>
                <td>{$upload.form.license.gpl}</td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_cc-by" /></td>
                <td>{$upload.form.license.cc_by}</td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_cc-by-sa" /></td>
                <td>{$upload.form.license.cc_by_sa}</td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_pd" /></td>
                <td>{$upload.form.license.pd}</td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_bsd" /></td>
                <td>{$upload.form.license.bsd}</td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td><input type="checkbox" name="license_other" /></td>
                <td>{$upload.form.license.other}</td>
            </tr>
            <tr>
                <td colspan="2" width="60"></td>
                <td colspan="2">
                    {$upload.form.license.other_remove}<br /><br />
                </td>
            </tr>
            <tr>
                <td><input type="checkbox" name="l_agreement" /></td>
                <td colspan="3">
                    {$upload.form.terms_removal} <strong>{$upload.form.required_text}</strong><br /><br />
                </td>
            </tr>
            <tr>
                <td><input type="checkbox" name="l_clean" /></td>
                <td colspan="3">
                    {$upload.form.terms.header}<br />
                    <ol>
                        {foreach $upload.form.terms.items as $item}
                            <li>{$item}</li>
                        {/foreach}
                    </ol>
                    <strong>{$upload.form.required_text}</strong>
                </td>
            </tr>
        </table>
        <script type="text/javascript">
            uploadFormFieldToggle();
        </script>
        <input type="submit" value="{$upload.form.upload_button}" />
        </form>
    {/if}
</div>
{include file=#footer#}