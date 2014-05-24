{config_load file="{$smarty.current_dir}/tpl.conf"}
<html>
    <head>
	<title>{$title|default:"Access Denied"}</title>
	<meta http-equiv="refresh" content="3;URL={$ad_redirect_url}" />
	<style type="text/css">
	#errpage_container {
	    background: #FFCCCC;
	    border: 1px solid #000000;
	    border-radius: 5px;
	    color: #000000;
	    font-family: sans-serif;
	    padding: 1em;
	    margin: auto;
	    margin-top: 2em;
	    text-align: center;
	    width: 400px;
	    -moz-border-radius: 5px;
	    -webkit-border-radius: 5px;
	}
	</style>
    </head>
    <body>
	<div id="errpage_container">
	    <p>{$ad_reason}</p>
	    <p>{$ad_action}</p>
	</div>
    </body>
</html>