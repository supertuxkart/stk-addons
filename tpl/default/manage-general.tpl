<h1>{t}General Settings{/t}</h1>
<form method="POST" action="manage.php?view=general&amp;action=save_config">
    <table>
        <tr>
            <td>{t}XML Download Frequency{/t}</td>
            <td>
                <input type="text" name="xml_frequency" value="{$general.xml_frequency}" size="6" maxlength="8">
            </td>
        </tr>
        <tr>
            <td>{t}Permitted Addon Filetypes{/t}</td>
            <td>
                <input type="text" name="allowed_addon_exts" value="{$general.allowed_addon_exts}">
            </td>
        </tr>
        <tr>
            <td>{t}Permitted Source Archive Filetypes{/t}</td>
            <td>
                <input type="text" name="allowed_source_exts" value="{$general.allowed_source_exts}">
            </td>
        </tr>
        <tr>
            <td>{t}Administrator Email{/t}</td>
            <td>
                <input type="text" name="admin_email" value="{$general.admin_email}">
            </td>
        </tr>
        <tr>
            <td>{t}Moderator List Email{/t}</td>
            <td>
                <input type="text" name="list_email" value="{$general.list_email}">
            </td>
        </tr>
        <tr>
            <td>{t}List Invisible Addons in XML{/t}</td>
            <td>
                {html_options name="list_invisible" options=$general.list_invisible.options selected=$general.list_invisible.selected}
            </td>
        </tr>
        <tr>
            <td>{t}Blog RSS Feed{/t}</td>
            <td>
                <input type="text" name="blog_feed" value="{$general.blog_feed}">
            </td>
        </tr>
        <tr>
            <td>{t}Maximum Uploaded Image Dimension{/t}</td>
            <td>
                <input type="text" name="max_image_dimension" value="{$general.max_image_dimension}">
            </td>
        </tr>
        <tr>
            <td>{t}Apache Rewrites{/t}</td>
            <td>
                <textarea name="apache_rewrites">{$general.apache_rewrites}</textarea>
            </td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" value="{t}Save Settings{/t}"></td>
        </tr>
    </table>
</form>