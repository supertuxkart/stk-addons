{config_load file="tpl/default/tpl.conf"}
{include file=#header#}

<div id="content">
    {include file=#errors#}
    {if $login.display==true}
        {$login.form.start}
        {$login.form.username.label}
        <br />
        {$login.form.username.field}
        <br />
        {$login.form.password.label}
        <br />
        {$login.form.password.field}
        <br />
        {$login.form.submit}
        <br />
        {$login.form.end}
        {$login.links.register}
        <br />
        {$login.links.reset_password}
        <br />
    {/if}
    {$confirmation|default:''}
</div>{* #content *}
{include file=#footer#}