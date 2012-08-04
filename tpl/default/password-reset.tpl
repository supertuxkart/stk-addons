{config_load file="tpl/default/tpl.conf"}
{include file=#header#}

<div id="content">
    <h1>{$pass_reset.title}</h1>
    
    {$pass_reset.info}

    {if $pass_reset.reset_form.display == true}
	{$pass_reset.reset_form.form.start}
	{$pass_reset.reset_form.info}
	<table>
	    <tr>
		<td>{$pass_reset.reset_form.username.label}</td>
		<td>{$pass_reset.reset_form.username.field}</td>
	    </tr>
	    <tr>
		<td>{$pass_reset.reset_form.email.label}</td>
		<td>{$pass_reset.reset_form.email.field}</td>
	    </tr>
	    <tr>
		<td>{$pass_reset.reset_form.captcha.label}</td>
		<td>{$pass_reset.reset_form.captcha.field}</td>
	    </tr>
	    <tr>
		<td></td>
		<td>{$pass_reset.reset_form.submit.field}</td>
	    </tr>
	</table>
	{$pass_reset.reset_form.form.end}
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
</div>{* #content *}
{include file=#footer#}