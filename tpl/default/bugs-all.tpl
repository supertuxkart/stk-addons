{if empty($bugs.items)}
    <div class="alert alert-info">
        <strong>{t}Empty!{/t}</strong> {t}There are no bugs :){/t}
    </div>
{else}
    <table class="table table-hover" id="bugs-all">
        <thead>
        <tr>
            <th>ID</th>
            <th>{t}Addon{/t}</th>
            <th>{t}Title{/t}</th>
            <th>{t}Changed{/t}</th>
        </tr>
        </thead>
        <tbody>
        {foreach $bugs.items as $item}
            <tr data-id="{$item.id}">
                <th class="bugs"><a href="{$smarty.const.BUGS_LOCATION}?bug_id={$item.id}">{$item.id}</a></th>
                <th>{$item.addon_id}</th>
                <th class="bugs"><a href="{$smarty.const.BUGS_LOCATION}?bug_id={$item.id}">{$item.title|truncate:30|escape}</a></th>
                <th>{$item.date_edit}</th>
            </tr>
        {/foreach}
        </tbody>
    </table>
{/if}