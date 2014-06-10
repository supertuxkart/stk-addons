<h2>{$bug.title|escape}</h2>
<table class="table">
    <tr>
        <td class="col-md-2">{t}Reported by{/t}</td>
        <td class="col-md-10">{$bug.user}</td>
    </tr>
    <tr>
        <td class="col-md-2">{t}Addon{/t}</td>
        <td class="col-md-10">{$bug.addon}</td>
    </tr>
    <tr>
        <td class="col-md-2">{t}Date report:{/t}</td>
        <td class="col-md-10">{$bug.date_report}</td>
    </tr>
    <tr>
        <td class="col-md-2">{t}Date edit:{/t}</td>
        <td class="col-md-10">{$bug.date_edit}</td>
    </tr>
    <tr>
        <td class="col-md-2">{t}Status:{/t}</td>
        <td class="col-md-10">test</td>
    </tr>
    <tr>
        <td class="col-md-2">{t}Description:{/t}</td>
        <td class="col-md-10">{$bug.description}</td>
    </tr>
</table>
<hr>
<div id="bug-comments-container">
    <h3>Comments</h3>
    {if $add_comment}
        <div id="alert-container-comments"></div>
        <form class="form-horizontal" id="bug-add-comment-form">
            <div class="form-group">
                <div class="col-md-12">
                    <textarea id="bug-comment-description" name="bug-comment-description" class="form-control" rows="8" placeholder="Description"></textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-offset-10 col-md-2">
                    <input type="hidden" name="action" value="add-comment">
                    <input type="hidden" name="bug-id" value="{$bug.id}">
                    <button type="submit" class="btn btn-info">{t}Add Comment{/t}</button>
                </div>
            </div>
        </form>
    {else}
        <p><a href="{$smarty.const.SITE_ROOT}login.php">{t}Login{/t}</a>{t} to add a comment{/t}</p>
    {/if}

    <div id="bug-comments">
        {foreach $bug.comments as $comment}
            {include file="bugs-view-comment.tpl" scope="parent"}
        {/foreach}
    </div>
</div>