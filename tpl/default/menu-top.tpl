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
                    {if $is_logged}
                        <span id="header-realname">{$menu.welcome}</span>&nbsp;&nbsp;&nbsp;
                    {/if}
                </p>
                <ul class="nav navbar-nav">
                    <li><a href="{$menu.home}">{t}Home{/t}</a></li>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-delay="500">
                            {t}Addons{/t} <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu" role="menu">
                            {if $is_logged}
                                <li>
                                    <a href="{$menu.upload}"><span class="glyphicon glyphicon-upload"></span> {t}Upload{/t}</a>
                                </li>
                            {/if}
                            <li><a href="{$menu.bugs}">{t}Bugs{/t}</a></li>
                            <li class="divider"></li>
                            <li><a href="{$menu.arenas}">{t}Arenas{/t}</a></li>
                            <li><a href="{$menu.karts}">{t}Karts{/t}</a></li>
                            <li><a href="{$menu.tracks}">{t}Tracks{/t}</a></li>
                        </ul>
                    </li>
                    {if $is_logged}
                        <li><a href="{$menu.users}">{t}Users{/t}</a></li>
                    {/if}
                    {if $can_edit_addons}
                        <li><a href="{$menu.manage}">{t}Manage{/t}</a></li>
                    {/if}
                    <li><a href="{$menu.stats}">{t}Stats{/t}</a></li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li id="lang-menu">{include file="./menu-lang.tpl"}</li>
                    <li><a href="http://supertuxkart.sourceforge.net">{t}STK Homepage{/t}</a></li>
                    {if !$is_logged}
                        <li><a href="{$menu.login}">{t}Login{/t}</a></li>
                    {else}
                        <li><a href="{$menu.logout}">{t}Logout{/t}</a></li>
                    {/if}
                </ul>
            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
</header>