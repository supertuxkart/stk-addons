{include file=$tpl_config.header}
<div class="row affix-row">
    <div class="col-sm-3 col-md-3 affix-sidebar left-menu">
        <div class="sidebar-nav">
            <div class="navbar navbar-default" role="navigation">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-navbar-collapse">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <span class="visible-xs navbar-brand">Sidebar menu</span>
                </div>
                <div class="navbar-collapse collapse sidebar-navbar-collapse">
                    <ul class="nav navbar-nav" id="manage-menu-sidenav">
                        <li>
                            <a href="?view=overview" class="manage-list">
                                <span class="glyphicon glyphicon-dashboard"></span> {t}Overview{/t}
                            </a>
                        </li>
                        {if isset($can_edit_roles) && $can_edit_roles}
                            <li>
                                <a href="?view=roles" class="manage-list">
                                    <span class="glyphicon glyphicon-wrench"></span> {t}Manage Roles{/t}
                                </a>
                            </li>
                        {/if}
                        {if isset($can_edit_settings) && $can_edit_settings}
                            <li>
                                <a href="?view=general" class="manage-list">
                                    <span class="glyphicon glyphicon-tasks"></span> {t}General Settings{/t}
                                </a>
                            </li>
                            <li>
                                <a href="?view=news" class="manage-list">
                                    <span class="glyphicon glyphicon-comment"></span> {t}News Messages{/t}
                                </a>
                            </li>
                            <li>
                                <a href="?view=clients" class="manage-list">
                                    <span class="glyphicon glyphicon-list-alt"></span> {t}Client Versions{/t}
                                </a>
                            </li>
                            <li>
                                <a href="?view=cache" class="manage-list">
                                    <span class="glyphicon glyphicon-file"></span> {t}Cache Files{/t}
                                </a>
                            </li>
                        {/if}
                        <li>
                            <a href="?view=files" class="manage-list">
                                <span class="glyphicon glyphicon-upload"></span> {t}Uploaded Files{/t}
                            </a>
                        </li>
                        <li>
                            <a href="?view=logs" class="manage-list">
                                <span class="glyphicon glyphicon-info-sign"></span> {t}Event Logs{/t}
                            </a>
                        </li>
                    </ul>
                </div><!--/.nav-collapse -->
            </div>
        </div>
    </div>
    <div class="col-sm-9 col-md-9 affix-content">
        <div class="container">
            <div id="manage-body">
                {$manage.body}
            </div>
        </div>
    </div>
</div>
{include file=$tpl_config.footer}