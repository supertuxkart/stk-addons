{if $warnings|default:''|count_characters != 0}
    <div class="alert alert-warning">
        {$warnings}
    </div>
{/if}