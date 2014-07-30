<h1>{t}Event Logs{/t}</h1>
<p>{t}The table below lists the most recent logged events.{/t}</p>

{if empty($logs.items)}
    <div class="alert alert-info">{t}No events have been logged yet.{/t}</div>
{else}
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <table class="table table-striped table-hover table-sort">
                    <thead>
                        <tr>
                            <th>{t}Date{/t}</th>
                            <th>{t}User{/t}</th>
                            <th>{t}Description{/t}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $logs.items as $item}
                            <tr>
                                <td>{$item.date}</td>
                                <td>{$item.name}</td>
                                <td>{$item.message}</td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
{/if}