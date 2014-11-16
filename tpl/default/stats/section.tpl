{if !empty($section.data)}
    <h2>{$section.title}</h2>
    <div>
        <p>
            {$section.description}
        </p>
        <table class="table table-bordered table-striped table-sort">
            <thead>
            <tr>
                {foreach $section.columns as $column}
                    <th>{$column}</th>
                {/foreach}
            </tr>
            </thead>
            <tbody>
            {foreach $section.data as $row}
                <tr>
                    {foreach $section.columns as $column}
                        <th>{$row.$column}</th>
                    {/foreach}
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
{/if}