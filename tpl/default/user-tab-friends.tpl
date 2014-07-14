<div class="tab-pane" id="friends">
    <br>
    {if empty($user.friends)}
        <div class="alert alert-info">
            <strong>{t}Empty!{/t}</strong> {t}There are no friends :({/t}
        </div>
    {else}
        <table class="table table-hover" id="bugs-table">
            <thead>
            <tr>
                <th>{t}Username{/t}</th>
                <th>{t}Date{/t}</th>
                {if $is_owner}
                    <th>{t}Actions{/t}</th>
                {/if}
            </tr>
            </thead>
            <tbody>
            {if $is_owner}
                {foreach $user.friends as $friend}
                    {$class=""}
                    {if $friend.is_pending}
                        {$class=" class=\"danger\""}
                    {/if}

                    <tr data-id="{$friend.id}"{$class}>
                        <td>{$friend.username}</td>
                        <td>{$friend.date}</td>
                        <td>
                            <div class="btn-group">
                                {if $friend.is_pending}
                                    {if $friend.is_asker}
                                        <button type="button" class="btn btn-success">{t}Accept friend request{/t}</button>
                                    {else}
                                        <button type="button" class="btn btn-warning">{t}Cancel friend request{/t}</button>
                                    {/if}
                                {else}
                                    <button type="button" class="btn btn-danger">{t}Remove friend{/t}</button>
                                {/if}
                            </div>
                        </td>
                    </tr>
                {/foreach}
            {else}
                {foreach $user.friends as $friend}
                    <tr>
                        <td>{$friend.username}</td>
                        <td>{$friend.date}</td>
                    </tr>
                {/foreach}
            {/if}
            </tbody>
        </table>

    {/if}
</div>