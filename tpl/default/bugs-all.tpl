{if empty($bugs.items)}
    <div class="alert alert-info">
        <strong>{t}Empty!{/t}</strong> {t}There are no bugs :){/t}
    </div>
{else}
    <table class="table table-hover">
        <thead>
        <tr>
            <th>#Id</th>
            <th>{t}Addon{/t}</th>
            <th>{t}Title{/t}</th>
            <th>{t}Changed{/t}</th>
        </tr>
        </thead>
        <tbody>
        {foreach $bugs.items as $item}
            <tr>
                <th class="bugs" data-id="{$item.id}"><a href="#">{$item.id}</a></th>
                <th>{$item.addon_id}</th>
                <th class="bugs" data-id="{$item.id}"><a href="#">{$item.title|truncate:30}</a></th>
                <th>{$item.date_edit}</th>
            </tr>
        {/foreach}
        </tbody>
    </table>
{/if}