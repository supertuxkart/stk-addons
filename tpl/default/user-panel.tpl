<ul class="nav nav-tabs" role="tablist" id="user-panel-nav">
    <li class="active"><a href="#profile" role="tab" data-toggle="tab">{t}Profile{/t}</a></li>
    <li><a href="#friends" role="tab" data-toggle="tab">{t}Friends{/t}</a></li>
    <li><a href="#settings" role="tab" data-toggle="tab">{t}Settings{/t}</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane active" id="profile">
        <h1>{$user.username|escape}</h1>

        <div class="container">
            <div class="row">
                <div class="col-md-3">{t}Username:{/t}</div>
                <div class="col-md-3">{$user.username|escape}</div>
            </div>
            <div class="row">
                <div class="col-md-3">{t}Registration Date:{/t}</div>
                <div class="col-md-3">{$user.date_registration|escape}</div>
            </div>
            <div class="row">
                <div class="col-md-3">{t}Real Name:{/t}</div>
                <div class="col-md-3">{$user.real_name|escape}</div>
            </div>
            <div class="row">
                <div class="col-md-3">{t}Role:{/t}</div>
                <div class="col-md-3">{$user.role|escape}</div>
            </div>
            {if !empty($user.homepage)}
                <div class="row">
                    <div class="col-md-3">{t}Homepage:{/t}</div>
                    <div class="col-md-3"><a href="{$user.homepage|escape}">{$user.homepage|escape}</a></div>
                </div>
            {/if}
        </div>
        {foreach $user.addon_types as $addon_type}
            <h1>{$addon_type.heading}</h1>
            {if !isset($addon_type.no_items)}
                <ul>
                    {*the list is already filtered in the code*}
                    {foreach $addon_type.list as $item}
                        <li class="{$item.css_class}">
                            <a href="addons.php?type={$addon_type.name}&name={$item.id}">{$item.name}</a>
                        </li>
                    {/foreach}
                </ul>
            {else}
                {$addon_type.no_items}
                <br>
            {/if}
        {/foreach}
    </div>
    <div class="tab-pane" id="friends">

    </div>
    <div class="tab-pane" id="settings">
        {if isset($user.config)}
            <hr>
            <h3>{t}Configuration{/t}</h3>
            <form class="form-horizontal" action="?user={$user.username|escape}&amp;action=config" method="POST">
                <div class="form-group">
                    <label>
                        {t}Homepage:{/t}
                        <input type="text" name="homepage" class="form-control" value="{$user.homepage|escape}">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        {t}Role:{/t}
                        <select class="form-control" name="range" {$user.config.role.disabled|default:""}>
                            {html_options options=$user.config.role.options selected=$user.config.role.selected|default:""}
                        </select>
                    </label>
                </div>
                <div class="form-group">
                    <div class="checkbox-inline">
                        <label>
                            <input type="checkbox" name="available" {$user.config.activated|default:""}>
                            {t}User Activated{/t}
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
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
                        <label>
                            {t}Old Password:{/t}<br>
                            <input type="password" class="form-control" name="oldPass">
                        </label>
                    </div>
                    <div class="form-group">
                    <label>
                            {t}New Password:{/t} ({t 1=$user.config.password.min}Must be at least %1 characters long{/t})<br>
                            <input type="password" name="newPass" class="form-control">
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            {t}New Password (Confirm):{/t}<br>
                            <input type="password" name="newPass2" class="form-control">
                        </label>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-warning" value="{t}Change Password{/t}">
                    </div>
                </form>
            {/if}
        {/if}
    </div>
</div>