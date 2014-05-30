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
        <td class="col-md-10">{$bug.description|escape}</td>
    </tr>
</table>
<hr>
<div id="bug-comments">
    <h3>Comments</h3>
    {foreach $bug.comments as $comment}
        {$c={counter}}
        <div class="panel panel-default" id="c{$c}">
            <div class="panel-heading clearfix">
                <h4 class="panel-title">{$comment.user_name|escape}
                    <span class="pull-right text-right">
                    <a href="#c{$c}">{$comment.date}</a>
                </span>
                </h4>
            </div>

            <div class="panel-body">
                {$comment.description|escape}
            </div>
        </div>
    {/foreach}
</div>