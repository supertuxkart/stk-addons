{config_load file="tpl/default/tpl.conf"}
{include file=#header#}

<div id="content">
    <h1>{$register.heading}</h1>
    {assign var='error_message' value=$errors|default:''}
    {if $error_message|count_characters != 0}<span class="error">{$error_message}</span>{/if}
    {if $register.display_form==true}
	{$register.form.start}
    <table>
        <tbody>
            <tr>
                <td>
                    {$register.form.username.label}<br />
                    <span style="font-size: x-small; color: #666666; font-weight: normal;">({$register.form.username.requirement})</span>
                </td>
                <td>
                    {$register.form.username.field}
                </td>
            </tr>
            <tr>
                <td>
                    {$register.form.password.label}<br />
                    <span style="font-size: x-small; color: #666666; font-weight: normal;">({$register.form.password.requirement})</span>
                </td>
                <td>
                    {$register.form.password.field}
                </td>
            </tr>
            <tr>
                <td>
                    {$register.form.password_conf.label}
                </td>
                <td>
                    {$register.form.password_conf.field}
                </td>
            </tr>
            <tr>
                <td>
                    {$register.form.name.label}
                </td>
                <td>
                    {$register.form.name.field}
                </td>
            </tr>
            <tr>
                <td>
                    {$register.form.email.label}<br />
                    <span style="font-size: x-small; color: #666666; font-weight: normal;">({$register.form.email.requirement})</span>
                </td>
                <td>
                    {$register.form.email.field}
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    {$register.form.terms.label}<br />
                    {$register.form.terms.field}
                </td>
            </tr>
            <tr>
                <td>
                    {$register.form.terms_agree.label}
                </td>
                <td>
                    {$register.form.terms_agree.field}
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    {$register.form.submit}
                </td>
            </tr>
        </tbody>
    </table>
	{$register.form.end}
    {/if}
    {$confirmation|default:''}
</div>{* #content *}
{include file=#footer#}