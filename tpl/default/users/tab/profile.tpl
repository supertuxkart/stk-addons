<div class="tab-pane active" id="profile">
    <div>
        <h1>
            {$user.username}
            {if !$is_owner}
                {*Friend buttons*}
                <div class="btn-group pull-right" data-id="{$user.user_id}" data-tab="profile">
                    {$class_accept=" hidden"} {$class_decline=" hidden"} {$class_cancel=" hidden"} {$class_already=" hidden"} {$class_send=" hidden"}
                    {if !empty($logged_friend)}
                        {if $logged_friend.is_pending}
                            {if $logged_friend.is_asker}
                                {$class_accept=""} {$class_decline=""}
                            {else}
                                {$class_cancel=""}
                            {/if}
                        {else}
                            {$class_already=""}
                        {/if}
                    {else}
                        {$class_send=""}
                    {/if}
                    <button type="button" class="btn btn-xs btn-default btn-accept-friend{$class_accept}">{t}Accept friend request{/t}</button>
                    <button type="button" class="btn btn-xs btn-default btn-decline-friend{$class_decline}">{t}Decline friend request{/t}</button>
                    <button type="button" class="btn btn-xs btn-default btn-cancel-friend{$class_cancel}">{t}Cancel friend request{/t}</button>
                    <button type="button" class="btn btn-xs btn-default btn-already-friend disabled{$class_already}">{t}Already friends{/t}</button>
                    <button type="button" class="btn btn-xs btn-default btn-send-friend{$class_send}">{t}Send friend Request{/t}</button>
                </div>
            {/if}
        </h1>
    </div>
    <div>
        <div class="row form-group">
            <div class="col-md-3">{t}Username:{/t}</div>
            <div class="col-md-3" id="user-username">{$user.username}</div>
        </div>
        <div class="row form-group">
            <div class="col-md-3">{t}Registration Date:{/t}</div>
            <div class="col-md-3">{$user.date_register}</div>
        </div>
        <div class="row form-group">
            <div class="col-md-3">{t}Real Name:{/t}</div>
            <div class="col-md-3" id="user-realname">{$user.real_name}</div>
        </div>
        <div class="row form-group">
            <div class="col-md-3">{t}Role:{/t}</div>
            <div class="col-md-3" id="user-role">{$user.role}</div>
        </div>
        {if $can_see_email}
            <div class="row form-group">
                <div class="col-md-3">{t}Email:{/t}</div>
                <div class="col-md-3">{$user.email}</div>
            </div>
        {/if}

        {$homepage_class=""}
        {if empty($user.homepage)}
            {$homepage_class=" hidden"}
        {/if}
        <div class="row form-group{$homepage_class}" id="user-homepage-row">
            <div class="col-md-3">{t}Homepage:{/t}</div>
            <div class="col-md-3" id="user-homepage"><a href="{$user.homepage}" target="_blank">{$user.homepage}</a></div>
        </div>
    </div>
    <div>
        {foreach $user.addon_types as $addon_type}
            <div>
                <h2>{$addon_type.heading}</h2>
                {if !empty($addon_type.items)}
                    <ul>
                        {*the list is already filtered in the code*}
                        {foreach $addon_type.items as $item}
                            <li class="{$item.css_class}">
                                <a href="addons.php?type={$addon_type.name}&amp;name={$item.id}">{$item.name|escape}</a>
                            </li>
                        {/foreach}
                    </ul>
                {else}
                    {$addon_type.no_items}
                    <br>
                {/if}
            </div>
        {/foreach}
    </div>
</div>