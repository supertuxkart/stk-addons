{if empty($users)}
    <p class="text-info">{t}No users match your search{/t}</p>
{else}
    <ul class="list-group">
        {foreach $users as $user}
            {$class=""}
            {if $user.active == 0}
                {$class=" unavailable"}
            {/if}
            <li class="list-group-item">
                <a href="users.php?user={$user.username|escape}" class="user-list{$class}">
                    <img class="icon" src="{$img_location}user.png">
                    <span>{$user.username|escape|truncate:24}</span>
                </a>
            </li>
        {/foreach}
    </ul>
{/if}
{$pagination}