{config_load file="{$smarty.current_dir}/tpl.conf"}
{include file=#header#}
<div id="content-bugs">
    <h1>{t}Bug Tracker{/t}
        <small> {t}for addons{/t}</small>
    </h1>
    <br>

    <div class="row">
        <div class="col-md-10">
            <form class="form-inline center-block" role="form">
                <div class="form-group">
                    <input type="text" class="form-control input-lg" id="bug-search"
                           placeholder="Enter bug id or bug title">
                </div>
                <div class="form-group">
                    <label>
                        <select class="form-control input-lg">
                            <option value="all" selected>All</option>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox-inline">
                        <input type="checkbox" id="search-description" name="search-description" value="description">Search
                                                                                                                     Description
                    </label>
                </div>
                <button type="submit" class="btn btn-info btn-lg">Search</button>
            </form>
        </div>
        <div class="col-md-2">
            <a class="btn btn-default btn-lg" href="#">
                File a bug
            </a>
        </div>
    </div>
    <br><br>
    <table class="table table-hover">
        <thead>
        <tr>
            <th>#Id</th>
            <th>Addon</th>
            <th>Title</th>
            <th>Changed</th>
        </tr>
        </thead>
        <tbody>
            {foreach $bugs.items as $item}
                <tr>
                    <th><a href="#">{$item.id}</a></th>
                    <th>{$item.addon_id}</th>
                    <th><a href="#">{$item.title|truncate:30}</a></th>
                    <th>{$item.date_edit}</th>
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>
{include file=#footer#}