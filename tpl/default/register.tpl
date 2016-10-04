{include file=$tpl_config.header}
<div class="row">
    <div class="col-md-offset-2">
        <h1 class="text-center">{t}Account Registration{/t}</h1>
        <hr>
        {include file="feedback/all.tpl"}
        {if $register.display}
            <form id="register" action="register.php?action=register" method="POST"
                  class="form-horizontal auto-validation"
                  data-bv-feedbackicons-valid="glyphicon glyphicon-ok"
                  data-bv-feedbackicons-invalid="glyphicon glyphicon-remove"
                  data-bv-feedbackicons-validating="glyphicon glyphicon-refresh">
                <div class="form-group">
                    <div class="col-md-3">
                        <label for="reg_user">{t}Username{/t}</label><br>
                        <span class="subtext">
                            ({t 1=$register.username.min}Must be at least %1 characters long.{/t})
                        </span>
                    </div>
                    <div class="col-md-7">
                        <input type="text" class="form-control" name="username" id="reg_user"
                               value="{$register.username.value}"
                               data-bv-notempty="true"
                               data-bv-notempty-message="{t}The username is required{/t}"

                               data-bv-stringlength="true"
                               data-bv-stringlength-min="{$register.username.min}"
                               data-bv-stringlength-max="{$register.username.max}"
                               data-bv-stringlength-message="{t 1=$register.username.min 2=$register.username.max}The username must be between %1 and %2 characters long{/t}"

                               data-bv-regexp="true"
                               data-bv-regexp-regexp="^[a-zA-Z0-9\.\-\_]+$"
                               data-bv-regexp-message="{t}Your username can only contain alphanumeric characters, periods, dashes and underscores{/t}"

                               data-bv-different="true"
                               data-bv-different-field="password"
                               data-bv-different-message="{t}The username and password cannot be the same as each other{/t}">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-3">
                        <label for="reg_pass">{t}Password{/t}</label><br>
                        <span class="subtext">
                            ({t 1=$register.password.min}Must be at least %1 characters long.{/t})
                        </span>
                    </div>
                    <div class="col-md-7">
                        <input type="password" class="form-control" name="password" id="reg_pass"
                               data-bv-notempty="true"
                               data-bv-notempty-message="{t}The password is required{/t}"

                               data-bv-stringlength="true"
                               data-bv-stringlength-min="{$register.password.min}"
                               data-bv-stringlength-max="{$register.password.max}"
                               data-bv-stringlength-message="{t 1=$register.password.min 2=$register.password.max}The password must be between %1 and %2 characters long{/t}"

                               data-bv-different="true"
                               data-bv-different-field="username"
                               data-bv-different-message="{t}The password cannot be the same as username{/t}">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-3">
                        <label for="reg_pass2">{t}Password (confirm){/t}</label>
                    </div>
                    <div class="col-md-7">
                        <input type="password" class="form-control" name="password_confirm" id="reg_pass2"
                               data-bv-notempty="true"
                               data-bv-notempty-message="{t}The confirm password is required{/t}"

                               data-bv-identical="true"
                               data-bv-identical-field="password"
                               data-bv-identical-message="{t}The passwords do not match{/t}">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-3">
                        <label for="reg_email">{t}Email Address{/t}</label><br>
                        <span class="subtext">
                            ({t}Email address used to activate your account.{/t})
                        </span>
                    </div>
                    <div class="col-md-7">
                        <input type="text" class="form-control" name="email" id="reg_email"
                               value="{$register.email.value}"
                               data-bv-notempty="true"
                               data-bv-notempty-message="{t}The registration email is required{/t}"

                               data-bv-emailaddress="true"
                               data-bv-emailaddress-message="{t}The email address is not a valid{/t}">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-3">
                        <label for="reg_name">{t}Name{/t}</label><br>
                        <span class="subtext">
                            ({t}Optional{/t})
                        </span>
                    </div>
                    <div class="col-md-7">
                        <input type="text" class="form-control" name="realname" id="reg_name"
                               value="{$register.realname.value}"

                               data-bv-stringlength="true"
                               data-bv-stringlength-min="{$register.realname.min}"
                               data-bv-stringlength-max="{$register.realname.max}"
                               data-bv-stringlength-message="{t 1=$register.realname.min 2=$register.realname.max}The nam must be between %1 and %2 characters long{/t}">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-3">
                        <label for="reg_terms">{t}Terms:{/t}</label>
                    </div>
                    <div class="col-md-7">
                        <textarea rows="20" cols="70" readonly id="reg_terms">
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
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-3">
                        <label for="reg_check">{t}I agree to the above terms{/t}</label>
                    </div>
                    <div class="col-md-7">
                        <input type="checkbox" class="input-lg" name="terms" id="reg_check"
                               data-bv-notempty="true"
                               data-bv-notempty-message="{t}You must agree to the above terms{/t}">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-3">
                        <label>{t}Verify that you are not a bot{/t}</label>
                    </div>
                    <div class="col-md-7">
                        <div class="g-recaptcha" data-sitekey="{$register.captcha_site_key}"></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-offset-3 col-md-3">
                        <input type="submit" class="btn btn-success btn-block" value="{t}Register!{/t}">
                    </div>
                </div>
            </form>
        {/if}
    </div>
</div>
{include file=$tpl_config.footer}