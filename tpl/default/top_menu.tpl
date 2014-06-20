<div id="top-menu">
    <div id="top-menu-content">
        <div class="left">
            {if $show_welcome==true}
                {$menu.welcome}&nbsp;&nbsp;&nbsp;
            {/if}
            {$menu.home}
            {if $show_login==true}
                {$menu.login}
            {else}
                {$menu.logout}
            {/if}
            {if $show_karts==true}
                {$menu.karts}
            {/if}
            {if $show_tracks==true}
                {$menu.tracks}
            {/if}
            {if $show_arenas==true}
                {$menu.arenas}
            {/if}
            {if $show_users==true}
                {$menu.users}
            {/if}
            {if $show_upload==true}
                {$menu.upload}
            {/if}
            {if $show_manage==true}
                {$menu.manage}
            {/if}
            {$menu.bugs}
        </div>
        <div class="right">
            {include file=#lang_menu#}
            {$menu.stk_home}
        </div>
    </div>
</div>