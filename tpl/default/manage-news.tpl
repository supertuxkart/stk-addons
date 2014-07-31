<h1>{t}News Messages{/t}</h1><hr>
<div class="row">
    <form method="POST" class="form-horizontal" action="manage.php?view=news&amp;action=new_news">
        <div class="form-group">
            <label for="news_message" class="col-md-3">
                {t}Message{/t}
            </label>
            <div class="col-md-5">
                <input type="text" name="message" class="form-control" id="news_message" size="60" maxlength="140">
            </div>
        </div>
        <div class="form-group">
            <label for="news_condition" class="col-md-3">
                {t}Condition{/t}
            </label>
            <div class="col-md-5">
                <input type="text" name="condition" class="form-control" id="news_condition" size="60" maxlength="255">
            </div>
        </div>
        <div class="form-group">
            <label for="web_display" class="col-md-3">
                {t}Display on Website:{/t}
            </label>
            <div class="col-md-5">
                <input type="checkbox" name="web_display" id="web_display" checked>
            </div>
        </div>
        <div class="form-group">
            <label for="important" class="col-md-3">
                {t}Important (creates notification):{/t}
            </label>
            <div class="col-md-5">
                <input type="checkbox" name="important" id="important">
            </div>
        </div>
        <div class="form-group">
            <div class="col-md-offset-3 col-md-2">
                <input type="submit" class="btn btn-success" value="{t}Create Message{/t}">
            </div>
        </div>
    </form><hr>
</div>
<div class="row">
    <div class="col-md-9">
        {if empty($news.items)}
            <p>{t}No news messages currently exist.{/t}</p>
        {else}
            <table class="table table-striped">
                <tr>
                    <th>{t}Date{/t}</th>
                    <th>{t}Message{/t}</th>
                    <th>{t}Author{/t}</th>
                    <th>{t}Condition{/t}</th>
                    <th>{t}Web{/t}</th>
                    <th>{t}Important{/t}</th>
                    <th>{t}Actions{/t}</th>
                </tr>
                {foreach $news.items as $item}
                    <tr>
                        <td>{$item.date}</td>
                        <td>{$item.content}</td>
                        <td>{$item.author}</td>
                        <td>{$item.condition}</td>
                        <td>{$item.web_display}</td>
                        <td>{$item.important}</td>
                        <td>
                            <form method="POST" action="manage.php?view=news&amp;action=del_news">
                                <input type="hidden" name="news_id" value="{$item.id}">
                                <input type="submit" class="btn btn-danger" value="{t}Delete{/t}">
                            </form>
                        </td>
                    </tr>
                {/foreach}
            </table>
        {/if}
    </div>
</div>
