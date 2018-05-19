<div class="tab-pane" id="settings"><br>
    <h3>{t}Profile{/t}</h3>
    <form class="form-horizontal" id="user-edit-profile">
        <div class="form-group">
            <label class="col-md-2 control-label" for="user-profile-homepage">
                {t}Homepage{/t}
            </label>
            <div class="col-md-6">
                <input type="text" name="homepage" id="user-profile-homepage" class="form-control" value="{$user.homepage}">
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-2 control-label" for="user-profile-realname">
                {t}Real name{/t}
            </label>
            <div class="col-md-6">
                <input type="text" name="realname" id="user-profile-realname" class="form-control" value="{$user.real_name}">
            </div>
        </div>
        <div class="form-group">
            <div class="col-md-offset-2 col-md-2">
                <input type="hidden" name="user-id" value="{$user.user_id}">
                <input type="hidden" name="action" value="edit-profile">
                <input type="submit" class="btn btn-success" value="{t}Save profile{/t}">
            </div>
        </div>
    </form>
    <hr>
    {if $can_edit_role}
        <h3>Edit user</h3>
        <form class="form-horizontal" id="user-edit-role">
            <div class="form-group">
                <label class="col-md-2 control-label" for="user-settings-role">
                    {t}Role{/t}
                </label>
                <div class="col-md-6">
                    <select class="form-control" id="user-settings-role" name="role">
                        {html_options options=$user.settings.elevate.options selected=$user.settings.elevate.selected|default:""}
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-offset-2 col-md-10">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" id="user-settings-available" name="available" {$user.settings.elevate.activated|default:""}> {t}User Activated{/t}
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-offset-2 col-md-2">
                    <input type="hidden" name="user-id" value="{$user.user_id}">
                    <input type="hidden" name="action" value="edit-role">
                    <input type="submit" class="btn btn-warning" value="{t}Edit{/t}">
                </div>
            </div>
        </form>
        <hr>
    {/if}
    {if $is_owner}
    <div class="bs-callout bs-callout-warning bg-warning border-warning">
        <h3 class="text-warning">{t}Change Password{/t}</h3>
        <br>
        <form class="form-horizontal" id="user-change-password">
            <div class="form-group">
                <label class="col-md-2 control-label" for="user-settings-old-pass">
                    {t}Old Password{/t}<br>
                </label>
                <div class="col-md-6">
                    <input type="password" class="form-control" id="user-settings-old-pass" name="old-pass">
                </div>
            </div>
            <div class="form-group">
                <label class="col-md-2 control-label" for="user-settings-new-pass">
                    {t}New Password{/t}
                </label>
                <div class="col-md-6">
                    <input type="password" name="new-pass" id="user-settings-new-pass" class="form-control">
                </div>
                <span class="help-block">
                    ({t 1=8}Must be at least %1 characters long{/t})
                </span>
            </div>
            <div class="form-group">
                <label class="col-md-2 control-label" for="user-settings-new-pass-verify">
                    {t}New Password (Confirm){/t}<br>
                </label>
                <div class="col-md-6">
                    <input type="password" name="new-pass-verify" id="user-settings-new-pass-verify" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-offset-2 col-md-2">
                    <input type="hidden" name="action" value="change-password">
                    <input type="submit" class="btn btn-warning" value="{t}Change Password{/t}">
                </div>
            </div>
        </form>
    </div>
    {/if}
    {if $is_owner}
    <div class="bs-callout bs-callout-danger bg-danger">
        <h3 class="text-danger">{t}Delete Account{/t}</h3>
        <form class="form-horizontal" id="user-delete-account">
            <div class="form-group">
                <div class="bs-callout bs-callout-warning bg-warning">
                    <p class="help-block">
                        {t}Once you delete your account, there is no going back. Please be certain.{/t}
                        <br>
                        {t}If you uploaded any addons, their owner will be set to no one and still be available on the website.{/t}
                    </p>
                </div>
                <div class="bs-callout bs-callout-warning bg-warning">
                    <p class="help-block">{t}This action cannot be undone. WE ARE SERIOUS. HERE BE DRAGONS.{/t}</p>
                </div>
                <label class="col-md-2 control-label" for="user-settings-delete-pass">
                    {t}Password{/t}
                </label>
                <div class="col-md-6">
                    <input type="password" name="password" id="user-settings-delete-pass" class="form-control">
                </div>
                <span class="help-block">
                ({t 1=8}Must be at least %1 characters long{/t})
                </span>
            </div>
            <div class="form-group">
                <label class="col-md-2 control-label" for="user-settings-verify-phrase">
                    {t}Verify Phrase:{/t}<br>
                </label>
                <div class="col-md-6">
                    <input type="text" class="form-control" id="user-settings-verify-phrase" name="verify-phrase">
                </div>
                <span class="help-block">
                    {t}Please type this verify phrase to confirm:{/t} <strong>DELETE/{$user.username}</strong>
                </span>
            </div>
            <div class="form-group">
                <div class="col-md-offset-2 col-md-2">
                    <input type="hidden" name="action" value="delete-account">
                    <input type="submit" class="btn btn-danger" value="{t}Delete your account{/t}">
                </div>
            </div>
        </form>
    </div>
    {/if}
</div>