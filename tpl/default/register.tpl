{config_load file="{$smarty.current_dir}/tpl.conf"}
{include file=#header#}

<div id="content">
    <h1>{t}Account Registration{/t}</h1>
    {include file=#feedback_errors#}
    {if $register.display_form==true}
        <form id="register" action="register.php?action=reg" method="POST">
            <table>
                <tbody>
                <tr>
                    <td>
                        <label for="reg_user">{t}Username:{/t}</label><br>
                        <span class="subtext">
                            ({t 1=$register.form.username.min}Must be at least %1 characters long.{/t})
                        </span>
                    </td>
                    <td>
                        <input type="text" name="user" id="reg_user" value="{$register.form.username.value}">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="reg_pass">{t}Password:{/t}</label><br>
                        <span class="subtext">
                            ({t 1=$register.form.password.min}Must be at least %1 characters long.{/t})
                        </span>
                    </td>
                    <td>
                        <input type="password" name="pass1" id="reg_pass">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="reg_pass2">{t}Password (confirm):{/t}</label>
                    </td>
                    <td>
                        <input type="password" name="pass2" id="reg_pass2">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="reg_name">{t}Name:{/t}</label>
                    </td>
                    <td>
                        <input type="text" name="name" id="reg_name" value="{$register.form.name.value}">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="reg_email">{t}Email Address:{/t}</label><br>
                        <span class="subtext">
                            ({t}Email address used to activate your account.{/t})
                        </span>
                    </td>
                    <td>
                        <input type="text" name="mail" id="reg_email" value="{$register.form.email.value}">
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <label for="reg_terms">{t}Terms:{/t}</label><br/>
                        <textarea rows="10" cols="90" readonly id="reg_terms">
=== {t}STK Addons Terms and Conditions{/t} ===

{t}You must agree to these terms in order to upload content to the STK Addons site.{/t}

{t}The STK Addons service is designed to be a repository exclusively for SuperTux Kart addon content. All uploaded content must be intended for this purpose. When you upload your content, it will be available publicly on the internet, and will be made available in-game for download.{/t}

{t}Super Tux Kart aims to comply with the Debian Free Software Guidelines (DFSG). TuxFamily.org also requires that content they host comply with open licenses. You may not upload content which is locked down with a restrictive license. Licenses such as CC-BY-SA 3.0, or other DFSG-compliant licenses are required. All content taken from third-party sources must be attributed properly, and must also be available under an open license. Licenses and attribution should be included in a "license.txt" file in each uploaded archive. Uploads without proper licenses or attribution may be deleted without warning.{/t}

{t}Even with valid licenses and attribution, content may not contain any of the following:{/t}
    1. {t}Profanity{/t}
    2. {t}Explicit images{/t}
    3. {t}Hateful messages and/or images{/t}
    4. {t}Any other content that may be unsuitable for children{/t}
{t}If any of your uploads are found to contain any of the above, your upload will be removed, your account may be removed, and any other content you uploaded may be removed.{/t}

{t}By checking the box below, you are confirming that you understand these terms. If you have any questions or comments regarding these terms, one of the members of the development team would gladly assist you.{/t}
                        </textarea>
                    </td>

                </tr>
                <tr>
                    <td>
                        <label for="reg_check">{t}I agree to the above terms{/t}</label>
                    </td>
                    <td>
                        <input type="checkbox" name="terms" id="reg_check">
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <input type="submit" value="{t}Register!{/t}">
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    {/if}
    {$confirmation|default:''}
</div>{* #content *}
{include file=#footer#}