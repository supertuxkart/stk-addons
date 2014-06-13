{config_load file="{$smarty.current_dir}/tpl.conf"}
{include file=#header#}

<div id="content">
    <div id="login-form">
    {include file=#feedback_errors#}
    {if $login.display==true}
        <form action="{$login.form.action}" method="POST" class="form-horizontal">
        <p>
            <label>
                {t}Username:{/t}
                <input type="text" name="user" class="form-control" placeholder="user">
            </label>
        </p>
        <p>
            <label>
                {t}Password:{/t}
                <input type="password" name="pass" class="form-control" placeholder="pass">
            </label>
        </p>
        <p>
            <input type="hidden" name="return_to" value="{$login.return_to}">
            <button type="submit" class="btn btn-primary">{t}Log In{/t}</button>
        </p>
        </form>
        {$login.links.register}
        <br />
        {$login.links.reset_password}
        <br />
    {/if}
    {$confirmation|default:''}
    </div>
</div>{* #content *}
{include file=#footer#}