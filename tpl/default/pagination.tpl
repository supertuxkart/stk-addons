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

        <li{$prev_class}><a href="{$pagination.url}?p={$prev_href}">&laquo;</a></li>
        <li{$first_class}><a href="{$pagination.url}?p=1">1</a></li>
        {if $pagination.nr_buttons + 3 > $pagination.total_pages}
            {*just display the buttons normally*}
            {for $i=2 to $pagination.total_pages - 1}
                {include file="pagination-inner-for.tpl" scope="parent"}
            {/for}
        {else}
            {*calculate where we are and build buttons*}
            {if $pagination.build_left && $pagination.build_right}
                {*build both sides*}
                {$nr_buttons = floor($pagination.nr_buttons/2)}

                <li><a href="#" class="disabled">...</a></li>
                {for $i = $pagination.current_page - $nr_buttons to $pagination.current_page - 1}
                    <li><a href="{$pagination.url}?p={$i}">{$i}</a></li>
                {/for}
                <li class="active"><a href="{$pagination.url}?p={$pagination.current_page}">{$pagination.current_page}</a></li>
                {for $i = $pagination.current_page + 1 to $pagination.current_page + $nr_buttons}
                    <li><a href="{$pagination.url}?p={$i}">{$i}</a></li>
                {/for}
                <li><a href="#" class="disabled">...</a></li>
            {else}
                {*build one side*}
                {if $pagination.build_left}
                    <li><a href="#" class="disabled">...</a></li>
                {else}
                    {for $i = 2 to $pagination.nr_buttons + 2}
                        {include file="pagination-inner-for.tpl" scope="parent"}
                    {/for}
                {/if}

                {if $pagination.build_right}
                    <li><a href="#" class="disabled">...</a></li>
                {else}
                    {for $i = ($pagination.total_pages - $pagination.nr_buttons) to $pagination.total_pages - 1}
                        {include file="pagination-inner-for.tpl" scope="parent"}
                    {/for}
                {/if}
            {/if}
        {/if}
        <li{$last_class}><a href="{$pagination.url}?p={$pagination.total_pages}">{$pagination.total_pages}</a></li>
        <li{$next_class}><a href="{$pagination.url}?p={$next_href}">&raquo;</a></li>
    </ul>
{/if}