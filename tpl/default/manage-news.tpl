<h1>{t}News Messages{/t}</h1>
<form method="POST" action="manage.php?view=news&amp;action=new_news">
    <table>
        <tr>
            <td>{t}Message:{/t}</td>
            <td>
                <input type="text" name="message" id="news_message" size="60" maxlength="140">
            </td>
        </tr>
        <tr>
            <td>{t}Condition:{/t}</td>
            <td>
                <input type="text" name="condition" id="news_condition" size="60" maxlength="255">
            </td>
        </tr>
        <tr>
            <td>{t}Display on Website:{/t}</td>
            <td>
                <input type="checkbox" name="web_display" id="web_display" checked>
            </td>
        </tr>
        <tr>
            <td>{t}Important (creates notification):{/t}</td>
            <td>
                <input type="checkbox" name="important" id="important">
            </td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" value="{t}Create Message{/t}"></td>
        </tr>
    </table>
</form>
<br>
{if !empty($news.items)}
    <table>
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
                        <input type="submit" value="{t}Delete{/t}">
                    </form>
                </td>
            </tr>
        {/foreach}
    </table>
{else}
    <p>{t}No news messages currently exist.{/t}</p>
{/if}