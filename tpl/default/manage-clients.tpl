<h1>{t}Client Versions{/t}</h1>
<h3>{t}Clients by User-Agent{/t}</h3>

{if empty($clients.items)}
    <hr>
    <div class="alert alert-info">{t}There are currently no SuperTuxKart clients recorded. Your download script may not be configured properly.{/t}</div>
{else}
    <div class="row">
        <div class="col-md-9">
            <table class="table table-striped table-hover table-no-sort">
                <thead>
                    <tr>
                        <th>{t}User-Agent String{/t}</th>
                        <th>{t}Game Version{/t}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $clients.items as $item}
                        <tr>
                            <td>{$item.agent_string}</td>
                            <td>{$item.stk_version}</td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
{/if}