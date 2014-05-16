<h1>{t}Event Logs{/t}</h1>
<p>{t}The table below lists the most recent logged events.{/t}</p>

{if !empty($logs.items)}
    <table width="100%">
        <tr>
            <th>{t}Date{/t}</th>
            <th>{t}User{/t}</th>
            <th>{t}Description{/t}</th>
        </tr>
        {foreach $logs.items as $item}
            <tr>
                <td>{$item.date}</td>
                <td>{$item.name}</td>
                <td>{$item.message}</td>
            </tr>
        {/foreach}
    </table>
{else}
    <p>{t}No events have been logged yet.{/t}</p>
{/if}