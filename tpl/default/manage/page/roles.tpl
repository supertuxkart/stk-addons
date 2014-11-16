<h1>{t}Manage Roles{/t}</h1>
<div id="manage-roles-body">
    <div class="row">
        <div class="col-md-4">
            <h3>Roles</h3>
            <div id="manage-roles-roles" class="btn-group-vertical">
                {foreach $roles.roles as $role}
                    <button type="button" class="btn btn-default">{$role}</button>
                {/foreach}
            </div>
            <hr>
            <div class="form-inline">
                <input type="text" placeholder="Add new role" id="manage-roles-add-value" class="form-control">
                <button type="button" id="manage-roles-add-btn" class="btn btn-success">Add role</button>
            </div>
            <hr>
            <div class="form-inline">
                <input type="text" placeholder="Select a role to edit" id="manage-roles-edit-value" class="form-control" disabled>
                <button type="button" id="manage-roles-edit-btn" class="btn btn-primary disabled">Edit role</button>
            </div>
            <hr>
            <div class="form-inline">
                <button type="button" id="manage-roles-delete-btn" class="btn btn-danger disabled">Delete role</button>
            </div>
        </div>
        <div class="col-md-8">
            <h3>Permissions</h3>
            <div id="manage-roles-permissions">
                <form class="form-horizontal" id="manage-roles-permission-form">
                    {foreach $roles.permissions as $permission}
                        <div class="checkbox">
                            <label class="checkbox-inline">
                                <input type="checkbox" class="manage-roles-permission-checkbox" name="permissions[]" value="{$permission}"> {$permission}
                            </label>
                        </div>
                    {/foreach}
                    <hr>
                    <div class="form-group">
                        <input type="hidden" id="manage-roles-permission-role" name="role" value="">
                        <input type="hidden" name="action" value="edit-role">
                        <button type="submit" class="btn btn-success">Update permissions</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>