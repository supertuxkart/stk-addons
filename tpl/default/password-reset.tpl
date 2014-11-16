{include file=$tpl_config.header}
<div id="main-reset-password">
    <h1>{t}Reset Password{/t}</h1>
    {include file="feedback/all.tpl"}
    {if $pass_reset.reset_form.display == true}
        <form id="reset_pw" action="?action=reset" class="form-horizontal" method="POST">
            <div class="form-group col-md-12">
                {t}In order to reset your password, please enter your username and your email address. A password reset link will be emailed to you. Your old password will become inactive until your password is reset.{/t}
            </div>
            <div class="form-group">
                <label for="reg_user" class="col-md-2">{t}Username:{/t}</label>
                <div class="col-md-4">
                    <input type="text" name="user" class="form-control" id="reg_user">
                </div>
            </div>
            <div class="form-group">
                <label for="reg_email" class="col-md-2">{t}Email Address:{/t}</label>
                <div class="col-md-4">
                    <input type="text" name="mail" class="form-control" id="reg_email">
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-offset-2 col-md-4">
                    {$pass_reset.reset_form.captcha}
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-offset-2 col-md-4">
                    <input type="submit" class="btn btn-primary" value="{t}Send Reset Link{/t}">
                </div>
            </div>
        </form>
    {/if}
    {if $pass_reset.pass_form.display == true}
        <form id="change_pw" action="?action=change" method="POST" class="form-horizontal">
            <div class="form-group col-md-12"">
                {t}Please enter a new password for your account.{/t}
            </div>
            <div class="form-group">
                <label for="reg_pass" class="col-md-3">
                    {t}New Password:{/t}<br>
                    <span class="subtext">
                    {t 1=8}Must be at least %1 characters long.{/t}
                    </span>
                </label>
                <div class="col-md-4">
                    <input type="password" class="form-control" name="pass1" id="reg_pass">
                </div>
            </div>
            <div class="form-group">
                <label for="reg_pass2" class="col-md-3">{t}New Password (confirm){/t}</label>
                <div class="col-md-4">
                    <input type="password" class="form-control" name="pass2" id="reg_pass2">
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-offset-3 col-md-4">
                    <input type="hidden" name="verify" value="{$pass_reset.pass_form.verification_code}">
                    <input type="hidden" name="user" value="{$pass_reset.pass_form.user_id}">
                    <input type="submit" class="btn btn-primary" value="{t}Change Password{/t}">
                </div>
            </div>
        </form>
    {/if}
</div>
{include file=$tpl_config.footer}