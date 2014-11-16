{include file=$tpl_config.header}
<div id="error-container">
    <div class="row">
        <div class="col-md-2">
            <!-- Image by Bryan Lunduke [lunduke.com]? Not sure of original source. -->
            <img src="{$smarty.const.IMG_LOCATION}sad-tux.png" alt="Sad Tux" width="200" height="160">
        </div>
        <div class="col-md-10">
            <h1>{$error.title}</h1>
            <p>{$error.message}</p>
        </div>
    </div>
</div>{* #error-container *}
{include file=$tpl_config.footer}