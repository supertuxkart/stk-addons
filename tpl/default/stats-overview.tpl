<div class="row">
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title"><a href="?addons">{t}Addons{/t}</a></h2>
            </div>
            <div class="panel-body">
                <div class="stats-pie-chart" data-json="{$overview.json.addons}"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title">
                    <a href="?files">{t}Files{/t} <small>{t}downloads (by add-on type){/t}</small></a>
                </h2>
            </div>
            <div class="panel-body">
                <div class="stats-pie-chart"  data-json="{$overview.json.files}"></div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title"><a href="?clients">{t}Clients{/t}</a></h2>
            </div>
            <div class="panel-body">
                <div data-json="{$overview.json.clients}"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title"><a href="?servers">{t}Servers{/t}</a></h2>
            </div>
            <div class="panel-body">
                <div data-json="{$overview.json.servers}"></div>
            </div>
        </div>
    </div>
</div>