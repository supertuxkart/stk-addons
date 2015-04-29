{include file=$tpl_config.header}
<div id="bugs-main">
    <h1 class="text-center">{t}Bug Tracker{/t}
        <small> {t}for addons{/t}</small>
    </h1>
    <br>
    <div class="row">
        <div class="col-md-10">
            <form class="form-inline center-block" role="form" id="bug-search-form">
                <div class="form-group">
                    <input type="text" class="form-control" id="search-val" name="query"
                           placeholder="Enter bug title">
                </div>
                <div class="form-group">
                    <select class="form-control" name="search-filter">
                        <option value="all" selected>{t}All{/t}</option>
                        <option value="open">{t}Open{/t}</option>
                        <option value="closed">{t}Closed{/t}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="checkbox-inline">
                        <input type="checkbox" id="search-description" name="search-description">{t}Search Description{/t}
                    </label>
                </div>
                <button type="submit" class="btn btn-info">{t}Search{/t}</button>
            </form>
        </div>
        <div class="col-md-2 text-right">
            {if !empty($bugs.show_btn_file) && $bugs.show_btn_file == true}
                {$btn_file_hide=""}
                {$btn_back_hide=" hidden"}
            {else}
                {$btn_file_hide=" hidden"}
                {$btn_back_hide=""}
            {/if}

            <button class="btn btn-default{$btn_file_hide}" id="btn-bugs-add">
                {t}File a bug{/t}
            </button>
            <button class="btn btn-default{$btn_back_hide}" id="btn-bugs-back">
                {t}Back{/t}
            </button>
        </div>
    </div>
    <br><br>
    <div id="bugs-body">
        {$bugs.content}
    </div>
</div>
{include file=$tpl_config.footer}