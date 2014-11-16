<ul class="nav nav-tabs" role="tablist" id="user-panel-nav">
    <li class="active"><a href="#profile" role="tab" data-toggle="tab">{t}Profile{/t}</a></li>
    <li><a href="#friends" role="tab" data-toggle="tab">{t}Friends{/t}</a></li>
    <li><a href="#achievements" role="tab" data-toggle="tab">{t}Achievements{/t}</a></li>
    {if $can_see_settings}
        <li><a href="#settings" role="tab" data-toggle="tab">{t}Settings{/t}</a></li>
    {/if}
</ul>
<div class="tab-content">
    {include file="./tab/profile.tpl" scope="parent"}
    {include file="./tab/friends.tpl" scope="parent"}
    {include file="./tab/achievements.tpl" scope="parent"}
    {if $can_see_settings}
        {include file="./tab/settings.tpl" scope="parent"}
    {/if}
</div>