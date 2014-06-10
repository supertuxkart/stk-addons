<div class="panel panel-default" id="c{$comment.id}">
    <div class="panel-heading clearfix">
        <h4 class="panel-title">{$comment.user_name|escape}
            <span class="pull-right text-right">
                <a href="#c{$comment.id}">{$comment.date}</a>
            </span>
        </h4>
    </div>
    <div class="panel-body">
        {$comment.description|escape}
    </div>
</div>