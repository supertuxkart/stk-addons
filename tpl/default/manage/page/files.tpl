<h1>{t}Uploaded Files{/t}</h1>
{if empty($upload.items)}
    <hr><div class="alert alert-info">{t}No files have been uploaded.{/t}</div>
{else}
    <div class="row">
        <div class="col-md-9">
            <table class="table table-striped table-hover table-no-sort">
                <thead>
                    <tr>
                        <th>{t}Name{/t}</th>
                        <th>{t}Type{/t}</th>
                        <th>{t}References{/t}</th>
                    </tr>
                </thead>
                <tbody>
                {$last_id=null}
                {foreach $upload.items as $item}
                    {if $last_id !== $item.addon_id}
                        {if $item.addon_id === false}
                            <tr>
                                <td><strong>{t}Unassociated{/t}</strong></td>
                                <td></td><td></td>
                            </tr>
                        {else}
                            <tr>
                                <td><strong>{$item.addon_id} ({$item.addon_type})</strong></td>
                                <td></td><td></td>
                            </tr>
                        {/if}
                    {/if}

                    <tr>
                        <td>{$item.file_path}</td>
                        <td>{$item.file_type}</td>
                        <td>
                            {if $item.file_type}
                                {if !$item.exists}
                                    <span class="label label-danger">{t}File not found on filesystem{/t}</span>
                                {/if}
                                {if empty($item.references)}
                                    <span class="label label-danger">{t}None{/t}</span>
                                {/if}
                                {$item.references}
                            {else}
                                <span class="label label-danger">{t}No record found in database{/t}</span>
                            {/if}
                        </td>
                    </tr>
                    {$last_id=$item.addon_id}
                {/foreach}
                </tbody>
            </table>
        </div>
    </div>
{/if}