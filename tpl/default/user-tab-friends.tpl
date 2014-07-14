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
                <th>{t}Since{/t}</th>
                {if $is_owner}
                    <th>{t}Status{/t}</th>
                    <th>{t}Actions{/t}</th>
                {/if}
            </tr>
            </thead>
            <tbody>
            {if $is_owner}
                {foreach $user.friends as $friend}
                    {$class=""}
                    {$is_pending=$friend->isPending()}
                    {$is_asker=$friend->isAsker()}
                    {if $is_pending}
                        {$class=" class=\"danger\""}
                    {/if}
                    <tr data-id="{$friend->getUser()->getId()}"{$class}>
                        <td>{$friend->getUser()->getUsername()}</td>
                        <td>{$friend->getDate()}</td>
                        <td>
                            {if $is_pending}
                                {t}Pending{/t}
                            {else}
                                {t}Offline{/t}
                            {/if}
                        </td>
                        <td>
                            <div class="btn-group">
                                {if $is_pending}
                                    {if $is_asker}
                                        <button type="button" class="btn btn-success">{t}Accept{/t}</button>
                                        <button type="button" class="btn btn-primary">{t}Decline{/t}</button>
                                    {else}
                                        <button type="button" class="btn btn-warning">{t}Cancel request{/t}</button>
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
                        <td>{$friend->getUser()->getUsername()}</td>
                        <td>{$friend->getDate()}</td>
                    </tr>
                {/foreach}
            {/if}
            </tbody>
        </table>

    {/if}
</div>