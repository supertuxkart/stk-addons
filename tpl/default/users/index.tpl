{include file=$tpl_config.header}
<div id="user-main">
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
        <div class="col-sm-3 col-md-3 left-menu" id="user-menu">
            {$user.menu}
        </div>
        <div class="col-sm-9 col-md-9">
            <div id="user-body">
                {$user.body}
            </div>
        </div>
    </div>
</div>
{include file=$tpl_config.footer}