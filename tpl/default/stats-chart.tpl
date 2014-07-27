<div class="panel panel-default">
    <div class="panel-heading stats-buttons-container">
        <h4 class="panel-title">
            <a href="?files">{$chart.title}</a>
        </h4>
        {if $chart.show_buttons}
            <div class="stats-buttons">
                <div class="btn-group" data-toggle="buttons">
                    <label class="btn btn-default active" data-date="1-year">
                        <input type="radio" name="stats-filter" checked> 1 Year
                    </label>
                    <label class="btn btn-default" data-date="6-months">
                        <input type="radio" name="stats-filter"> 6 Months
                    </label>
                    <label class="btn btn-default" data-date="1-month">
                        <input type="radio" name="stats-filter"> 1 Month
                    </label>
                </div>
            </div>
        {/if}
    </div>
    <div class="panel-body">
        <div class="{$chart.class}" data-json="{$chart.json}"></div>
    </div>
</div>