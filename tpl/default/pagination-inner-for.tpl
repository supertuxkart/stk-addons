{if $i === $pagination.current_page}
    <li class="active"><a href="{$pagination.url}?p={$i}">{$i}</a></li>
{else}
    <li><a href="{$pagination.url}?p={$i}">{$i}</a></li>
{/if}