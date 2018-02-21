{include file=$tpl_config.header}
<div>
    <h1>{t}About STK Add-Ons{/t}</h1>
    <h2>SuperTuxKart</h2>
    <p>{t}SuperTuxKart is a Free 3D kart racing game, with many tracks, characters and items for you to try.{/t}</p>
    <p>{t}Since version 0.7.1, SuperTuxKart has had the ability to fetch important messages from the STKAddons website. Since 0.7.2, the game has included a built-in add-on manager.{/t}</p>
    <p>{t 1='50' 2='60' 3='15'}SuperTuxKart now has over %1 karts, %2 tracks, and %3 arenas available in-game thanks to the add-on service.{/t}</p>
    <p>{t}Of course, the artists who create all of this content must be thanked too. Without them, the add-on website would not be as great as it is today.{/t}</p>
    <p>
        <a href="https://supertuxkart.net/">{t}Website{/t}</a> | <a href="https://supertuxkart.net/Donate">{t}Donate{/t}</a>
    </p>

    <h2>{t}Credits{/t}</h2>
    <pre>{$about.credits.content}</pre>
</div>
{include file=$tpl_config.footer}