{if empty($bugs.items)}
    <div class="alert alert-info">
        <strong>{t}Empty!{/t}</strong> {t}There are no bugs :){/t}
    </div>
{else}
    <table class="table table-hover" id="bugs-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>{t}Addon{/t}</th>
            <th>{t}Title{/t}</th>
            <th>{t}Status{/t}</th>
            <th>{t}Changed{/t}</th>
        </tr>
        </thead>
        <tbody>
        {foreach $bugs.items as $item}
            <tr data-id="{$item.id}">
                <th class="bugs"><a href="{$smarty.const.BUGS_LOCATION}?bug_id={$item.id}">{$item.id}</a></th>
                <th><a href="{$smarty.const.SITE_ROOT}addons.php?name={$item.addon_id}">{$item.addon_id}</a></th>
                <th class="bugs"><a href="{$smarty.const.BUGS_LOCATION}?bug_id={$item.id}">{$item.title|truncate:30|escape}</a></th>
                <th>
                    {if $item.close_id}
                        <span class="label label-danger">{t}closed{/t}</span>
                    {else}
                        <span class="label label-success">{t}open{/t}</span>
                    {/if}
                </th>
                <th>{$item.date_edit}</th>
            </tr>
        {/foreach}
        </tbody>
    </table>
{/if}