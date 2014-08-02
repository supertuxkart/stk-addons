{config_load file="{$smarty.current_dir}/tpl.conf"}
{include file=#header#}
<div id="addon-main">
    <div class="row">
        <div class="col-md-12">
            <div id="addon-top">
                <div id="addon-sort" class="btn-group">
                    <button type="button" data-type="featured" class="btn btn-default active">{t}Featured{/t}</button>
                    <button type="button" data-type="alphabetical" data-asc="glyphicon-sort-by-alphabet" data-desc="glyphicon-sort-by-alphabet-alt" class="btn btn-default btn-sortable">
                        {t}Alphabetical{/t}
                        <span class="glyphicon glyphicon-sort"></span>
                    </button>
                    <button type="button" data-type="date" data-asc="glyphicon-sort-by-attributes" data-desc="glyphicon-sort-by-attributes-alt" class="btn btn-default btn-sortable">
                        {t}Date{/t}
                        <span class="glyphicon glyphicon-sort"></span>
                    </button>
                </div>
                <form class="form-inline" role="form" id="addon-search-form">
                    <div class="form-group has-feedback">
                        <select id="addon-search-by" class="multiselect" multiple="multiple">
                            <option value="name" selected>{t}By Name{/t}</option>
                            <option value="description">{t}By Description{/t}</option>
                            <option value="designer">{t}By Designer{/t}</option>
                        </select>
                        <input type="text" class="form-control input-md" id="addon-search-val" placeholder="Search addons">
                        <span class="glyphicon glyphicon-search form-control-feedback"></span>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-2 col-md-2 left-menu" id="addon-menu">
            {$addon.menu}
        </div>
        <div class="col-sm-10 col-md-10">
            <div id="addon-status">
                {$addon.status}
            </div>
            <div id="addon-body">
                {if $is_name && empty($addon.body)}
                    <br>
                    <div class="alert alert-danger">
                        {t}The addon name does not exist{/t}
                    </div>
                {else}
                    {$addon.body}
                {/if}
            </div>
        </div>
    </div>
</div>
{include file=#footer#}