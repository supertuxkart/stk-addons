<h1>{$user.username.value}</h1>
<table>
    <tr>
        <td>{t}Username:{/t}</td>
        <td>{$user.username.value}</td>
    </tr>
    <tr>
        <td>{t}Registration Date:{/t}</td>
        <td>{$user.reg_date.value}</td>
    </tr>
    <tr>
        <td>{t}Real Name:{/t}</td>
        <td>{$user.real_name.value}</td>
    </tr>
    <tr>
        <td>{t}Role:{/t}</td>
        <td>{$user.role.value}</td>
    </tr>
    {if !empty($user.homepage.value)}
        <tr>
            <td>{t}Homepage:{/t}</td>
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
    <h3>{t}Configuration{/t}</h3>
    <form enctype="multipart/form-data" action="?user={$user.username.value}&amp;action=config" method="POST">
        <table>
            <tr>
                <td>{t}Homepage:{/t}</td>
                <td><input type="text" name="homepage" value="{$user.homepage.value}"></td>
            </tr>
            <tr>
                <td>{t}Role:{/t}</td>
                <td>
                    <select name="range" {$user.config.role.disabled|default:""}>
                        {html_options options=$user.config.role.options selected=$user.config.role.selected|default:""}
                    </select>
                </td>
            </tr>
            <tr>
                <td>{t}User Activated:{/t}</td>
                <td>
                    <input type="checkbox" name="available" {$user.config.activated|default:""}>
                </td>
            </tr>
            <tr>
                <td></td>
                <td><input type="submit" value="{t}Save Configuration{/t}"></td>
            </tr>
        </table>
    </form>
    <br>
    {if isset($user.config.password)}
        <h3>{t}Change Password{/t}</h3>
        <br>
        <form action="users.php?user={$user.username.value}&amp;action=password" method="POST">
            <p>
                <label>
                    {t}Old Password:{/t}<br>
                    <input type="password" name="oldPass">
                </label>
            </p>
            <p>
                <label>
                    {t}New Password:{/t} ({t 1=$user.config.password.min}Must be at least %1 characters long{/t})<br>
                    <input type="password" name="newPass">
                </label>
            </p>
            <p>
                <label>
                    {t}New Password (Confirm):{/t}<br>
                    <input type="password" name="newPass2">
                </label>
            </p>
            <input type="submit" value="{t}Change Password{/t}">
        </form>
    {/if}
{/if}