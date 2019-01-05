{include file=$tpl_config.header}

<div class="row">
    <h1 class="text-center">{t}Rankings{/t}</h1>
</div>

{foreach $player_rankings.sections as $section}
    {$section}
{/foreach}

<div>
    <p>Play official servers to have your name in!</p>
    <p>Visit <a href="https://supertuxkart.net/About_player_rankings">here</a> for details about player rankings.</p>
</div>
{include file=$tpl_config.footer}
