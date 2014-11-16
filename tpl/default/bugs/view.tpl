<div class="clearfix">
    <h2 class="pull-left" id="bug-view-title">
        {$bug.title|escape}
    </h2>
    <div class="pull-right">
    {if $can_edit_bug}
        <div class="btn-group">
            <button type="button" id="btn-bugs-edit" class="btn btn-primary">{t}Edit{/t}</button>
            {if !$bug.is_closed}
                <button type="button" id="btn-bugs-close" class="btn btn-warning">{t}Close{/t}</button>
            {/if}
            {if $can_delete_bug}
                <button type="button" id="btn-bugs-delete" class="btn btn-danger">{t}Delete{/t}</button>
            {/if}
        </div>
        {if !$bug.is_closed}
            <div class="modal fade" id="modal-close" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title">{t}Close bug{/t}</h4>
                        </div>
                        <form id="modal-close-form" class="form-horizontal">
                            <div class="modal-body">
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <textarea name="modal-close-reason" id="modal-close-reason" class="form-control" rows="5" placeholder="{t}Close Reason{/t}"></textarea>
                                    </div>
                                    <input type="hidden" name="action" value="close">
                                    <input type="hidden" name="bug-id" value="{$bug.id}">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">{t}Back{/t}</button>
                                <input type="submit" class="btn btn-primary" value="{t}Submit{/t}">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        {/if}
        <div class="modal fade" id="modal-edit" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title">{t}Edit bug{/t}</h4>
                    </div>
                    <form id="modal-edit-form" class="form-horizontal">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="bug-title-edit" class="col-md-2">
                                    {t}Title:{/t}
                                </label>
                                <div class="col-md-10">
                                    <input type="text" value="{$bug.title|escape}" name="bug-title-edit" id="bug-title-edit" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="bug-description-edit" class="col-md-2">
                                    {t}Description:{/t}
                                </label>
                                <div class="col-md-10">
                                    <textarea id="bug-description-edit" name="bug-description-edit" class="form-control" rows="10">{$bug.description}</textarea>
                                </div>
                            </div>
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" id="bug-id" name="bug-id" value="{$bug.id}">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">{t}Back{/t}</button>
                            <input type="submit" class="btn btn-primary" value="{t}Update{/t}">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    {/if}
    </div>
</div>
<table class="table">
    <tr>
        <td class="col-md-2">{t}Reported by{/t}:</td>
        <td class="col-md-10"><a href="{$smarty.const.SITE_ROOT}users.php?user={$bug.user_name}">{$bug.user_name}</a>  {t}on{/t} {$bug.date_report}</td>
    </tr>
    <tr>
        <td class="col-md-2">{t}Addon{/t}:</td>
        <td class="col-md-10"><a href="{$smarty.const.SITE_ROOT}addons.php?name={$bug.addon}">{$bug.addon}</a></td>
    </tr>
    <tr>
        <td class="col-md-2">{t}Date edit{/t}:</td>
        <td class="col-md-10">{$bug.date_edit}</td>
    </tr>
    {if $bug.is_closed}
        <tr>
            <td class="col-md-2">{t}Closed by{/t}:</td>
            <td class="col-md-10"><a href="{$smarty.const.SITE_ROOT}users.php?user={$bug.user_name}">{$bug.user_name}</a> {t}on{/t} {$bug.date_close}</td>
        </tr>
        <tr>
            <td class="col-md-2">{t}Close reason{/t}:</td>
            <td class="col-md-10">{$bug.close_reason}</td>
        </tr>
        <tr>
            <td class="col-md-2">{t}Status{/t}:</td>
            <td class="col-md-10"><span id="bug-view-status" class="label label-danger" >{t}closed{/t}</span></td>
        </tr>
    {else}
        <tr>
            <td class="col-md-2">{t}Status{/t}:</td>
            <td class="col-md-10"><span class="label label-success">{t}open{/t}</span></td>
        </tr>
    {/if}
    <tr>
        <td class="col-md-2">{t}Description:{/t}</td>
        <td class="col-md-10" id="bug-view-description">{$bug.description}</td>
    </tr>
</table>
<hr>
<div id="bug-comments-container">
    <h3>Comments</h3>
    {if $can_add_comment}
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
        <p>
            <a href="{$smarty.const.SITE_ROOT}login.php?return_to={$current_url}"> {t}Login{/t}</a>{t} to add a comment{/t}
        </p>
    {/if}

    {if $can_edit_bug}
        <div class="modal fade" id="modal-comment-edit" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title">{t}Edit comment{/t}</h4>
                    </div>
                    <form id="modal-comment-edit-form" class="form-horizontal">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="bug-comment-edit-description" class="col-md-2">
                                    {t}Description:{/t}
                                </label>
                                <div class="col-md-10">
                                    <textarea id="bug-comment-edit-description" name="bug-comment-edit-description" class="form-control" rows="10"></textarea>
                                </div>
                                <input type="hidden" name="action" value="edit-comment">
                                <input type="hidden" name="comment-id" id="modal-comment-edit-id" value="">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">{t}Back{/t}</button>
                            <input type="submit" class="btn btn-primary" value="{t}Update{/t}">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    {/if}

    <div id="bug-comments">
        {foreach $bug.comments as $comment}
            {include file="./view-comment.tpl" scope="parent"}
        {/foreach}
    </div>
</div>