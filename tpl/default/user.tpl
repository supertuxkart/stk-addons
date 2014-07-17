{config_load file="{$smarty.current_dir}/tpl.conf"}
{include file=#header#}
<div class="container" id="user-main">
    <div class="row">
        <div class="col-md-12">
            <form class="form-inline" role="form" id="user-search-form">
                <div class="form-group has-feedback">
                    <input type="text" class="form-control input-md" id="user-search-val" placeholder="Search users">
                    <span class="glyphicon glyphicon-search form-control-feedback"></span>
                </div>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-2 col-md-2 left-menu" id="user-menu">
            {$user.menu}
        </div>
        <div class="col-sm-10 col-md-10">
            <div id="user-body">
                {$user.body}
            </div>
        </div>
    </div>
</div>
{include file=#footer#}