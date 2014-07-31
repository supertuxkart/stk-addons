<h1>{t}General Settings{/t}</h1><hr>
<form method="POST" class="form-horizontal" id="manage-general-form" action="manage.php?view=general&amp;action=save_config">
    <div class="form-group">
        <label for="xml_frequency" class="col-md-3">
            {t}XML Download Frequency{/t}
        </label>
        <div class="col-md-6">
            <input type="text" class="form-control" id="xml_frequency" name="xml_frequency" value="{$general.xml_frequency}" maxlength="8">
        </div>
    </div>
    <div class="form-group">
        <label for="allowed_addon_exts" class="col-md-3">
            {t}Permitted Addon Filetypes{/t}
        </label>
        <div class="col-md-6">
            <input type="text" class="form-control" id="allowed_addon_exts" name="allowed_addon_exts" value="{$general.allowed_addon_exts}">
        </div>
    </div>
    <div class="form-group">
        <label for="allowed_source_exts" class="col-md-3">
            {t}Permitted Source Archive Filetypes{/t}
        </label>
        <div class="col-md-6">
            <input type="text" class="form-control" id="allowed_source_exts" name="allowed_source_exts" value="{$general.allowed_source_exts}">
        </div>
    </div>
    <div class="form-group">
        <label for="admin_email" class="col-md-3">
            {t}Administrator Email{/t}
        </label>
        <div class="col-md-6">
            <input type="email" class="form-control" id="admin_email" name="admin_email" value="{$general.admin_email}">
        </div>
    </div>
    <div class="form-group">
        <label for="list_email" class="col-md-3">
            {t}Moderator List Email{/t}
        </label>
        <div class="col-md-6">
            <input type="email" class="form-control" id="list_email" name="list_email" value="{$general.list_email}">
        </div>
    </div>
    <div class="form-group">
        <label for="list_invisible" class="col-md-3">
            {t}List Invisible Addons in XML{/t}
        </label>
        <div class="col-md-6">
            <select id="list_invisible" name="list_invisible">
                {html_options options=$general.list_invisible.options selected=$general.list_invisible.selected}
            </select>
        </div>
    </div>
    <div class="form-group">
        <label for="blog_feed" class="col-md-3">
            {t}Blog RSS Feed{/t}
        </label>
        <div class="col-md-6">
            <input type="text" class="form-control" id="blog_feed" name="blog_feed" value="{$general.blog_feed}">
        </div>
    </div>
    <div class="form-group">
        <label for="max_image_dimension" class="col-md-3">
            {t}Maximum Uploaded Image Dimension{/t}
        </label>
        <div class="col-md-6">
            <input type="text" class="form-control" id="max_image_dimension" name="max_image_dimension" value="{$general.max_image_dimension}">
        </div>
    </div>
    <div class="form-group">
        <label for="apache_rewrites" class="col-md-3">
            {t}Apache Rewrites{/t}
        </label>
        <div class="col-md-6">
            <textarea class="form-control" id="apache_rewrites" name="apache_rewrites">{$general.apache_rewrites}</textarea>
        </div>
    </div>
    <div class="form-group">
        <div class="col-md-offset-3 col-md-2">
            <input type="submit" class="btn btn-success" value="{t}Save Settings{/t}">
        </div>
    </div>
</form>