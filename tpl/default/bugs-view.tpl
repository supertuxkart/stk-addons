<h2>
    {$bug.title|escape}
    {if $can_edit_bug}
        <div class="pull-right">
            <div class="btn-group">
                <button type="button" id="btn-bugs-edit" class="btn btn-primary">Edit</button>
                {if !$bug.is_closed}
                    <button type="button" id="btn-bugs-close" class="btn btn-danger">Close</button>
                {/if}
            </div>
        </div>
        <div class="modal fade" id="modal-close" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title" id="myModalLabel">Close bug</h4>
                    </div>
                    <form id="modal-close-form" class="form-horizontal">
                    <div class="modal-body">
                            <div class="form-group">
                                <div class="col-md-12">
                                    <textarea name="modal-close-reason" id="modal-close-reason" class="form-control" rows="5" placeholder="Close Reason"></textarea>
                                </div>
                                <input type="hidden" name="action" value="close">
                                <input type="hidden" name="bug-id" value="{$bug.id}">
                                <input type="hidden" name="author-id" value="{$bug.user_id}">
                            </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Back</button>
                        <input type="submit" class="btn btn-primary" value="Submit">
                    </div>
                    </form>
                </div>
            </div>
        </div>
    {/if}
</h2>
<table class="table">
    <tr>
        <td class="col-md-2">{t}Reported by{/t}:</td>
        <td class="col-md-10">{$bug.user_name}</td>
    </tr>
    <tr>
        <td class="col-md-2">{t}Addon{/t}:</td>
        <td class="col-md-10">{$bug.addon}</td>
    </tr>
    <tr>
        <td class="col-md-2">{t}Date report{/t}:</td>
        <td class="col-md-10">{$bug.date_report}</td>
    </tr>
    <tr>
        <td class="col-md-2">{t}Date edit{/t}:</td>
        <td class="col-md-10">{$bug.date_edit}</td>
    </tr>
    {if $bug.is_closed}
        <tr>
            <td class="col-md-2">{t}Date close{/t}:</td>
            <td class="col-md-10">{$bug.date_close}</td>
        </tr>
        <tr>
            <td class="col-md-2">{t}Closed by{/t}:</td>
            <td class="col-md-10">{$bug.user_name}</td>
        </tr>
        <tr>
            <td class="col-md-2">{t}Close reason{/t}:</td>
            <td class="col-md-10">{$bug.close_reason}</td>
        </tr>
        <tr>
            <td class="col-md-2">{t}Status{/t}:</td>
            <td class="col-md-10"><span class="label label-danger">{$bug.status}</span></td>
        </tr>
    {else}
        <tr>
            <td class="col-md-2">{t}Status{/t}:</td>
            <td class="col-md-10"><span class="label label-success">{$bug.status}</span></td>
        </tr>
    {/if}
    <tr>
        <td class="col-md-2">{t}Description:{/t}</td>
        <td class="col-md-10">{$bug.description}</td>
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
        <p><a href="{$smarty.const.SITE_ROOT}login.php?return_to={$current_url}">{t}Login{/t}</a>{t} to add a comment{/t}</p>
    {/if}

    <div id="bug-comments">
        {foreach $bug.comments as $comment}
            {include file="bugs-view-comment.tpl" scope="parent"}
        {/foreach}
    </div>
</div>