{config_load file="{$smarty.current_dir}/tpl.conf"}
{include file=#header#}
<div id="stats-main">
    <h1 class="text-center">Statistics</h1>
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h2 class="panel-title">Addons</h2>
                    </div>
                    <div class="panel-body">

                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h2 class="panel-title">Files <small>downloads (by add-on type)</small></h2>
                    </div>
                    <div class="panel-body">
                        <div class="stats-pie-chart" id="stat-files" data-json="TODO add url">

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h2 class="panel-title">Clients</h2>
                    </div>
                    <div class="panel-body">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h2 class="panel-title">Servers</h2>
                    </div>
                    <div class="panel-body">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{include file=#footer#}