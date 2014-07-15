{if $pagination.has_pagination}
    <ul class="pagination">
        {$prev_class=""} {$first_class=""} {$prev_href="#"}
        {if $pagination.prev_page}
            {$prev_href=$pagination.prev_page}
        {else}
            {$prev_class=" class=\"disabled\""}
            {$first_class=" class=\"active\""}
        {/if}
        {$next_class=""} {$last_class=""} {$next_href="#"}
        {if $pagination.next_page}
            {$next_href=$pagination.next_page}
        {else}
            {$next_class=" class=\"disabled\""}
            {$last_class=" class=\"active\""}
        {/if}

        {$sum_buttons=2}
        <li{$prev_class}><a href="{$pagination.url}?{$prev_href}">&laquo;</a></li>
        <li{$first_class}><a href="{$pagination.url}?p=1">1</a></li>
        {for $i=2 to $pagination.total_pages - 1}
            {if $i === $pagination.current_page}
                <li class="active"><a href="{$pagination.url}?p={$i}">{$i}</a></li>
            {else}
                <li><a href="{$pagination.url}?p={$i}">{$i}</a></li>
            {/if}
        {/for}
        <li{$last_class}><a href="{$pagination.url}?p={$pagination.total_pages}">{$pagination.total_pages}</a></li>
        <li{$next_class}><a href="{$pagination.url}?{$next_href}">&raquo;</a></li>
    </ul>
{/if}