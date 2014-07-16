{if empty($menu_users)}
    <p class="text-info">{t}No users match your search{/t}</p>
{else}
    <ul class="list-group">
        {foreach $menu_users as $user_data}
            {$class=""}
            {if $user_data.active == 0}
                {$class=" unavailable"}
            {/if}
            <li class="list-group-item">
                <a href="users.php?user={$user_data.username|escape}" class="user-list{$class}">
                    <img class="icon" src="{$img_location}user.png">
                    <span>{$user_data.username|escape|truncate:24}</span>
                </a>
            </li>
        {/foreach}
    </ul>
{/if}
{$pagination}