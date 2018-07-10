{include file=$tpl_config.header}
<div id="login-main">
    {include file="feedback/all.tpl"}
    {if $login.display}
        <div class="col-md-4 col-md-offset-4">
            <div class="panel panel-default">
                <div class="panel-heading"><h3 class="panel-title"><strong>{t}Login{/t}</strong></h3></div>
                <div class="panel-body">
                    <form class="auto-validation" role="form" method="POST" action="{$login.form_action}"
                          data-bv-feedbackicons-valid="glyphicon glyphicon-ok"
                          data-bv-feedbackicons-invalid="glyphicon glyphicon-remove"
                          data-bv-feedbackicons-validating="glyphicon glyphicon-refresh">
                        <div class="form-group">
                            <label for="login-username">{t}Username{/t}</label>
                            <input type="text" id="login-username" name="username" class="form-control" placeholder="{t}Enter username{/t}"
                                   value="{$login.username.value}"
                                   data-bv-notempty="true"
                                   data-bv-notempty-message="{t}The username is required{/t}"

                                   data-bv-stringlength="true"
                                   data-bv-stringlength-min="{$login.username.min}"
                                   data-bv-stringlength-max="{$login.username.max}"
                                   data-bv-stringlength-message="{t 1=$login.username.min 2=$login.username.max}The username must be between %1 and %2 characters long{/t}"

                                   data-bv-regexp="true"
                                   data-bv-regexp-regexp="^[a-zA-Z0-9\.\-\_ ]+$"
                                   data-bv-regexp-message="{t}Your username can only contain alphanumeric characters, periods, dashes and underscores{/t}"

                                   data-bv-different="true"
                                   data-bv-different-field="password"
                                   data-bv-different-message="{t}The username and password cannot be the same as each other{/t}">
                        </div>
                        <div class="form-group">
                            <label for="login-password">{t}Password{/t} {$login.links.reset_password}</label>
                            <input type="password" class="form-control" name="password" id="login-password" placeholder="{t}Password{/t}"
                                   data-bv-notempty="true"
                                   data-bv-notempty-message="{t}The password is required{/t}"

                                   data-bv-stringlength="true"
                                   data-bv-stringlength-min="{$login.password.min}"
                                   data-bv-stringlength-max="{$login.password.max}"
                                   data-bv-stringlength-message="{t 1=$login.password.min 2=$login.password.max}The password must be between %1 and %2 characters long{/t}"

                                   data-bv-different="true"
                                   data-bv-different-field="username"
                                   data-bv-different-message="{t}The password cannot be the same as username{/t}">
                        </div>
                        <div class="form-group">
                            <input type="hidden" name="return_to" value="{$login.return_to}">
                            <button type="submit" class="btn btn-success btn-block">{t}Login{/t}</button>
                        </div>
                        <hr>
                        {t}Don't have an account?{/t} {$login.links.register}
                    </form>
                </div>
            </div>
        </div>
    {/if}
</div>
{include file=$tpl_config.footer}