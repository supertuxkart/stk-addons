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
                            <option value="all" selected>{t}All{/t}</option>
                            <option value="open">{t}Open{/t}</option>
                            <option value="closed">{t}Closed{/t}</option>
                        </select>
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox-inline">
                        <input type="checkbox" id="search-description" name="search-description"
                               value="description">{t}Search Description{/t}
                    </label>
                </div>
                <button type="submit" class="btn btn-info btn-lg">{t}Search{/t}</button>
            </form>
        </div>
        <div class="col-md-2">
            <button class="btn btn-default btn-lg" id="btn-file-a-bug">
                {t}File a bug{/t}
            </button>
        </div>
    </div>
    <br><br>

    <div id="bug-content">
        <table class="table table-hover">
            <thead>
            <tr>
                <th>#Id</th>
                <th>{t}Addon{/t}</th>
                <th>{t}Title{/t}</th>
                <th>{t}Changed{/t}</th>
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
</div>
{include file=#footer#}