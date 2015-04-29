{if $success|default:''|count_characters != 0}
    <div class="alert alert-success">
        {$success}
    </div>
{/if}