<ul class="nav nav-tabs" role="tablist" id="user-panel-nav">
    <li class="active"><a href="#profile" role="tab" data-toggle="tab">{t}Profile{/t}</a></li>
    <li><a href="#friends" role="tab" data-toggle="tab">{t}Friends{/t}</a></li>
    <li><a href="#settings" role="tab" data-toggle="tab">{t}Settings{/t}</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane active" id="profile">
        <h1>{$user.username|escape}</h1>

        <div class="container">
            <div class="row form-group">
                <div class="col-md-3">{t}Username:{/t}</div>
                <div class="col-md-3">{$user.username|escape}</div>
            </div>
            <div class="row form-group">
                <div class="col-md-3">{t}Registration Date:{/t}</div>
                <div class="col-md-3">{$user.date_registration|escape}</div>
            </div>
            <div class="row form-group">
                <div class="col-md-3">{t}Real Name:{/t}</div>
                <div class="col-md-3">{$user.real_name|escape}</div>
            </div>
            <div class="row form-group">
                <div class="col-md-3">{t}Role:{/t}</div>
                <div class="col-md-3">{$user.role|escape}</div>
            </div>
            {if !empty($user.homepage)}
                <div class="row form-group">
                    <div class="col-md-3">{t}Homepage:{/t}</div>
                    <div class="col-md-3"><a href="{$user.homepage|escape}">{$user.homepage|escape}</a></div>
                </div>
            {/if}
        </div>
        <div>
            {foreach $user.addon_types as $addon_type}
                <div>
                    <h2>{$addon_type.heading}</h2>
                    {if !empty($addon_type.items)}
                        <ul>
                            {*the list is already filtered in the code*}
                            {foreach $addon_type.items as $item}
                                <li class="{$item.css_class}">
                                    <a href="addons.php?type={$addon_type.name}&amp;name={$item.id}">{$item.name|escape}</a>
                                </li>
                            {/foreach}
                        </ul>
                    {else}
                        {$addon_type.no_items}
                        <br>
                    {/if}
                </div>
            {/foreach}
        </div>

    </div>
    <div class="tab-pane" id="friends">

    </div>
    <div class="tab-pane" id="settings">
        {if isset($user.config)}
            <hr>
            <h3>{t}Configuration{/t}</h3>
            <form class="form-horizontal" action="?user={$user.username|escape}&amp;action=config" method="POST">
                <div class="form-group">
                    <label class="col-md-2 control-label" for="user-settings-homepage">
                        {t}Homepage{/t}
                    </label>
                    <div class="col-md-6">
                        <input type="text" name="homepage" id="user-settings-homepage" class="form-control" value="{$user.homepage|escape}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label" for="user-settings-role">
                        {t}Role{/t}
                    </label>
                    <div class="col-md-6">
                        <select class="form-control" id="user-settings-role" name="range" {$user.config.role.disabled|default:""}>
                            {html_options options=$user.config.role.options selected=$user.config.role.selected|default:""}
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-offset-2 col-md-10">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="available" {$user.config.activated|default:""}> {t}User Activated{/t}
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-offset-2 col-md-2">
                        <input type="submit" class="btn btn-success" value="{t}Save Configuration{/t}">
                    </div>
                </div>
            </form>
            <br>
            {if isset($user.config.password)}
                <h3>{t}Change Password{/t}</h3>
                <br>
                <form class="form-horizontal" action="users.php?user={$user.username}&amp;action=password" method="POST">
                    <div class="form-group">
                        <label class="col-md-2 control-label">
                            {t}Old Password{/t}<br>
                        </label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" name="oldPass">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">
                            {t}New Password{/t}
                        </label>
                        <div class="col-md-6">
                            <input type="password" name="newPass" class="form-control">
                        </div>
                        <span class="help-block">
                            ({t 1=$user.config.password.min}Must be at least %1 characters long{/t})
                        </span>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">
                            {t}New Password (Confirm){/t}<br>
                        </label>
                        <div class="col-md-6">
                            <input type="password" name="newPass2" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-md-offset-2 col-md-2">
                            <input type="submit" class="btn btn-warning" value="{t}Change Password{/t}">
                        </div>
                    </div>
                </form>
            {/if}
        {/if}
    </div>
</div>