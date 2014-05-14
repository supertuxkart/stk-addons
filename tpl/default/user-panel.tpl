<h1>{$user.username.value}</h1>
<table>
    <tr>
        <td>{$user.username.label}</td>
        <td>{$user.username.value}</td>
    </tr>
    <tr>
        <td>{$user.reg_date.label}</td>
        <td>{$user.reg_date.value}</td>
    </tr>
    <tr>
        <td>{$user.real_name.label}</td>
        <td>{$user.real_name.value}</td>
    </tr>
    <tr>
        <td>{$user.role.label}</td>
        <td>{$user.role.value}</td>
    </tr>
    {if isset($user.homepage.value) && !empty($user.homepage.value)}
        <tr>
            <td>{$user.homepage.label}</td>
            <td>{$user.homepage.value}</td>
        </tr>
    {/if}
</table>

{foreach $user.addon_types as $addon_type}
    <h1>{$addon_type.heading}</h1>
    {if !isset($addon_type.no_items)}
        <ul>
            {*the list is already filtered in the code*}
            {foreach $addon_type.list as $item}
                <li class="{$item.css_class}">
                    <a href="addons.php?type={$addon_type.name}&name={$item.id}">{$item.name}</a>
                </li>
            {/foreach}
        </ul>
    {else}
        {$addon_type.no_items}
        <br>
    {/if}
{/foreach}

{if isset($user.config)}
    <hr>
    <h3>{$user.config.header}</h3>
    <form enctype="multipart/form-data" action="?user={$user.username.value}&action=config" method="POST">
        <table>
            <tr>
                <td>{$user.homepage.label}</td>
                <td><input type="text" name="homepage" value="{$user.homepage.value}"></td>
            </tr>
            <tr>
                <td>{$user.role.label}</td>
                <td>
                    <select name="range" {$user.config.role.disabled|default:""}>
                        {html_options options=$user.config.role.options selected=$user.config.role.selected|default:""}
                    </select>
                </td>
            </tr>
            <tr>
                <td>{$user.config.activated_label}</td>
                <td>
                    <input type="checkbox" name="available" {$user.config.activated|default:""}>
                </td>
            </tr>
            <tr>
                <td></td>
                <td><input type="submit" value="{$user.config.submit_value}"></td>
            </tr>
        </table>
    </form>
    <br>
    {if isset($user.config.password)}
        <h3>{$user.config.password.header}</h3>
        <br>
        <form action="users.php?user={$user.username.value}&action=password" method="POST">
            {$user.config.password.old_pass_label}<br>
            <input type="password" name="oldPass"><br>
            {$user.config.password.new_pass_label}<br>
            <input type="password" name="newPass"><br>
            {$user.config.password.new_pass_conf_label}<br>
            <input type="password" name="newPass2"><br>
            <input type="submit" value="{$user.config.password.submit_value}">
        </form>
    {/if}
{/if}