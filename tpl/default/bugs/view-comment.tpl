<div class="panel panel-default" id="c{$comment.id}">
    <div class="panel-heading">
        <h4 class="panel-title clearfix">{$comment.user_name}
            <div class="pull-right">
                {if isset($can_edit_comment) && $can_edit_comment}
                    <div class="btn-group">
                        <a href="#c{$comment.id}" class="btn btn-link">{$comment.date}</a>
                        <button type="button" class="btn btn-link dropdown-toggle" data-toggle="dropdown">
                            <span class="caret"></span>
                            <span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li><a href="#" class="btn-bugs-comments-edit" data-id="{$comment.id}">{t}Edit{/t}</a></li>
                            <li><a href="#" class="btn-bugs-comments-delete" data-id="{$comment.id}">{t}Delete{/t}</a></li>
                        </ul>
                    </div>
                {else}
                    <a href="#c{$comment.id}">{$comment.date}</a>
                {/if}
            </div>
        </h4>
    </div>
    <div class="panel-body">
        {$comment.description}
    </div>
</div>