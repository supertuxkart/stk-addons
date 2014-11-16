{$checked_1=""} {$checked_2=""} {$checked_3=""}
{if $rating_1}
    {$checked_1=" checked"}
{/if}
{if $rating_2}
    {$checked_2=" checked"}
{/if}
{if $rating_3}
    {$checked_3=" checked"}
{/if}

<span id="user-rating" data-id="{$addon_id}">
    <input type="radio" name="rating" id="rating-1" class="add-rating" value="1"{$checked_1}>
    <label for="rating-1">
        <div class="rating">
            <div class="emptystars"></div>
            <div class="fullstars" style="width: 33%"></div>
        </div>
    </label><br> {*1 star*}
    <input type="radio" name="rating" id="rating-2" class="add-rating" value="2"{$checked_2}>
    <label for="rating-2">
        <div class="rating">
            <div class="emptystars"></div>
            <div class="fullstars" style="width: 66%"></div>
        </div>
    </label><br> {*2 stars*}
    <input type="radio" name="rating" id="rating-3" class="add-rating" value="3"{$checked_3}>
    <label for="rating-3">
        <div class="rating">
            <div class="emptystars"></div>
            <div class="fullstars" style="width: 100%"></div>
        </div>
    </label> {*3 stars*}
</span>