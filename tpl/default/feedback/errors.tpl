{assign var='error_message' value=$errors|default:''}
{if $error_message|count_characters != 0}<span class="error">{$error_message}</span>{/if}