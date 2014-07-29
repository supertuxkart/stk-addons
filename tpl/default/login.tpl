{config_load file="{$smarty.current_dir}/tpl.conf"}
{include file=#header#}
<div id="container" class="login-main">
    {include file=#feedback_errors#}
    <div id="login-form">
        {include file="feedback/errors.tpl"}
        {include file="feedback/warnings.tpl"}
        {include file="feedback/success.tpl"}
    {if $login.display}
        <div class="col-md-4 col-md-offset-4">
            <div class="panel panel-default">
                <div class="panel-heading"><h3 class="panel-title"><strong>{t}Login{/t}</strong></h3></div>
                <div class="panel-body">
                    <form role="form" method="POST" action="{$login.form_action}">
                        <div class="form-group">
                            <label for="login-username">{t}Username{/t}</label>
                            <input type="text" id="login-username" name="username" class="form-control" placeholder="{t}Enter username{/t}">
                        </div>
                        <div class="form-group">
                            <label for="login-password">{t}Password{/t} {$login.links.reset_password}</label>
                            <input type="password" class="form-control" name="password" id="login-password" placeholder="Password">
                        </div>
                        <input type="hidden" name="return_to" value="{$login.return_to}">
                        <button type="submit" class="btn btn-success btn-block">{t}Login{/t}</button>
                        <hr>
                        {t}Don't have an account!{/t} {$login.links.register}
                    </form>
                </div>
            </div>
        </div>
    {/if}
    </div>
</div>
{include file=#footer#}