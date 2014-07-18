<div class="tab-pane" id="achievements"><br>
    {if empty($user.achievements)}
        <div class="alert alert-info">
            <strong>{t}Empty!{/t}</strong> {t}There are no achievements :({/t}
        </div>
    {else}
        <ul>
            {foreach $user.achievements as $achievement}
                <li>{$achievement}</li>
            {/foreach}
        </ul>
    {/if}
</div>