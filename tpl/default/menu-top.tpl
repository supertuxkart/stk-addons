<header>
    <nav class="navbar navbar-default navbar-static-top navbar-fixed-top" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
            </div>

            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <p class="navbar-text navbar-left">
                    {if $show_welcome==true}
                        {$menu.welcome}&nbsp;&nbsp;&nbsp;
                    {/if}
                </p>
                <ul class="nav navbar-nav">
                    <li>{$menu.home}</li>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">{t}Addons{/t} <span class="caret"></span></a>
                        <ul class="dropdown-menu" role="menu">
                            <li>{$menu.arenas}</li>
                            <li>{$menu.karts}</li>
                            <li>{$menu.tracks}</li>
                        </ul>
                    </li>
                    {if $show_users==true}
                        <li>{$menu.users}</li>
                    {/if}
                    {if $show_upload==true}
                        <li>{$menu.upload}</li>
                    {/if}
                    {if $show_manage==true}
                        <li>{$menu.manage}</li>
                    {/if}
                    <li>{$menu.bugs}</li>
                    <li>{$menu.stats}</li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li id="lang-menu">{include file=#lang_menu#}</li>
                    <li>{$menu.stk_home}</li>
                    {if $show_login==true}
                        <li>{$menu.login}</li>
                    {else}
                        <li>{$menu.logout}</li>
                    {/if}
                </ul>
            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
</header>