<h1>{t}Client Versions{/t}</h1>
<h3>{t}Clients by User-Agent{/t}</h3>

{if !empty($clients.items)}
    <table>
        <tr>
            <th>{t}User-Agent String{/t}</th>
            <th>{t}Game Version{/t}</th>
        </tr>
        {foreach $clients.items as $item}
            <tr>
                <td>{$item.agent_string}</td>
                <td>{$item.stk_version}</td>
            </tr>
        {/foreach}
    </table>
{else}
    <p>{t}There are currently no SuperTuxKart clients recorded. Your download script may not be configured properly.{/t}</p>
{/if}