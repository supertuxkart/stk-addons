<h1>{t}Uploaded Files{/t}</h1>

{if !empty($upload.items)}
    <table class="info">
        <thead>
            <tr>
                <th>{t}Name{/t}</th>
                <th>{t}Type{/t}</th>
                <th>{t}References{/t}</th>
            </tr>
        </thead>
        <tbody>
            {foreach $upload.items as $item}
                <tr>
                    <td>{$item.file_path}</td>
                    <td>{$item.file_type}</td>
                    <td>{$item.references}</td>
                </tr>
            {/foreach}
        </tbody>
    </table>
{else}
    <p>{t}No files have been uploaded.{/t}</p>
{/if}