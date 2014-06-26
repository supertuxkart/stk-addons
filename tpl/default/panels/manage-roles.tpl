<h1>{t}Manage Roles{/t}</h1>
<div class="container" id="manage-roles-body">
    <div class="row">
        <div class="col-md-4">
            <h3>Roles</h3>

            <div id="manage-roles-roles" class="btn-group-vertical">
                {foreach $roles.roles as $role}
                    <button type="button" class="btn btn-default">{$role}</button>
                {/foreach}
            </div>
        </div>
        <div class="col-md-8">
            <h3>Permissions</h3>

            <div id="manage-roles-permissions">
                <form class="form-horizontal" id="manage-roles-permission-form">
                    {foreach $roles.permissions as $permission}
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" class="manage-roles-permission-checkbox" name="permissions[]" value="{$permission}"> {$permission}
                            </label>
                        </div>
                    {/foreach}
                    <hr>
                    <div class="form-group">
                        <input type="hidden" id="manage-roles-permission-role" name="role" value="">
                        <input type="hidden" name="action" value="edit-role">
                        <button type="submit" class="btn btn-success">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>