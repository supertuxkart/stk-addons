{config_load file="tpl/default/tpl.conf"}
{include file=#header#}

<div id="content">
    <h1>{t}About STK Add-Ons{/t}</h1>
    <h2>SuperTuxKart</h2>
    <p>{t}SuperTuxKart is a Free 3D kart racing game, with many tracks, characters and items for you to try.{/t}</p>
    <p>{t}Since version 0.7.1, SuperTuxKart has had the ability to fetch important messages from the STKAddons website. Since 0.7.2, the game has included a built-in add-on manager.{/t}</p>
    <p>{t 1='50' 2='60' 3='15'}SuperTuxKart now has over %1 karts, %2 tracks, and %3 arenas available in-game thanks to the add-on service.{/t}</p>
    <p>{t}Of course, the artists who create all of this content must be thanked too. Without them, the add-on website would not be as great as it is today.{/t}</p>
    <p>
        <a href="http://supertuxkart.sourceforge.net/">{t}Website{/t}</a> | <a href="http://sourceforge.net/donate/index.php?group_id=202302">{t}Donate{/t}</a>
    </p>

    <h2>TuxFamily</h2>
    <p>{t}TuxFamily is a non-profit organization. It provides free services for projects and contents dealing with the free software philosophy (free as in free speech, not as in free beer). They accept any project released under a free license (GPL, BSD, CC-BY-SA, Art Libre...).{/t}</p>
    <p>{t}TuxFamily operates the servers on which STKAddons runs, for free. Because of them, we can provide the add-on service for SuperTuxKart. Each month, over a million downloads are made by SuperTuxKart players. We thank them very much for their generosity to us and to other open source projects.{/t}</p>
    <p>
        <a href="http://tuxfamily.org/">{t}Website{/t}</a> | <a href="http://tuxfamily.org/en/support">{t}Donate{/t}</a>
    </p>


    <h2>{t}Credits{/t}</h2>
    <pre>{$about.credits.content}</pre>
</div>{* #content *}
{include file=#footer#}