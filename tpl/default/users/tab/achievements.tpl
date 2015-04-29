<div class="tab-pane" id="achievements"><br>
    {if empty($user.achievements)}
        <div class="alert alert-info">
            <strong>{t}Empty!{/t}</strong> {t}There are no achievements :({/t}
        </div>
    {else}
        <table class="table table-striped">
            <tr>
                <th>#ID</th>
                <th>{t}Name{/t}</th>
            </tr>
            {foreach $user.achievements as $achievement}
                <tr>
                    <td>{$achievement.id}</td>
                    <td>{$achievement.name}</td>
                </tr>
            {/foreach}
        </table>
    {/if}
</div>