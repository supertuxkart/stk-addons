{if empty($users)}
    <p class="text-info">{t}No users match your search{/t}</p>
{else}
    <div class="list-group">
        {foreach $users as $user}
            <a href="users.php?user={$user.username}" class="{$user.class}list-group-item user-list">
                <img class="icon" src="{$img_location}user.png">
                <span>{$user.username|truncate:28}</span>
            </a>
        {/foreach}
    </div>
{/if}
{$pagination}