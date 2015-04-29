{if $errors|default:''|count_characters != 0}
    <div class="alert alert-danger">
        {$errors}
    </div>
{/if}