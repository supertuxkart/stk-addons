{config_load file="{$smarty.current_dir}/tpl.conf"}
{include file=#header#}

<div id="content">
    <h1>{t}Reset Password{/t}</h1>

    <p>{$pass_reset.info}</p>

    {if $pass_reset.reset_form.display == true}
        <form id="reset_pw" action="password-reset.php?action=reset" method="POST">
            <p>
                {t}In order to reset your password, please enter your username and your email address. A password reset link will be emailed to you. Your old password will become inactive until your password is reset.{/t}
            </p>
            <table>
                <tr>
                    <td><label for="reg_user">{t}Username:{/t}</label></td>
                    <td><input type="text" name="user" id="reg_user"></td>
                </tr>
                <tr>
                    <td><label for="reg_email">{t}Email Address:{/t}</label></td>
                    <td><input type="text" name="mail" id="reg_email"></td>
                </tr>
                <tr>
                    <td></td>
                    <td>{$pass_reset.reset_form.captcha.field}</td>
                </tr>
                <tr>
                    <td></td>
                    <td><input type="submit" value="{t}Send Reset Link{/t}"></td>
                </tr>
            </table>
        </form>
    {/if}
    {if $pass_reset.pass_form.display == true}
        {$pass_reset.pass_form.form.start}
        {$pass_reset.pass_form.info}
        <table>
            <tr>
                <td>{$pass_reset.pass_form.new_pass.label}</td>
                <td>{$pass_reset.pass_form.new_pass.field}</td>
            </tr>
            <tr>
                <td>{$pass_reset.pass_form.new_pass2.label}</td>
                <td>{$pass_reset.pass_form.new_pass2.field}</td>
            </tr>
            <tr>
                <td></td>
                <td>{$pass_reset.pass_form.submit.field}</td>
            </tr>
        </table>
        {$pass_reset.pass_form.form.end}
    {/if}
</div>

{include file=#footer#}