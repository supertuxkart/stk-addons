STK Addons Website
==================

This is the source code for the SuperTuxKart asset sharing and distribution
platform. The official location of the operating website is http://www.stkaddons.net/.

Installing Locally
------------------

This software has no automated installation mechanism as it is not intended for wide
usage. However, you can manually generate all of the necessary database tables and
configuration settings manually.

Before you attempt to set up a local installation, you should run check_server_deps.php
on your web server. This will check to make sure that several dependencies can be found.
There may be other dependencies not tested by that script, but that should be enough to
get started.

You can generate the database tables, procedures, and relations by using a tool such as
PHPMyAdmin to import the table.sql file found in the repository. You may need to edit
the provided SQL file, as it assumes a table prefix of 'v2_' and a database username of
'stkaddons_stkbase'.

On your web server, you must edit the provided config-base.php to match your database
and system configuration. Save this file as config.php. Enable the debugging mode in
the configuration file to assist with resolving any errors.

Download Smarty (http://www.smarty.net/) and make sure it is located in your PHP
include path, as directed by the PHP errors that will appear if Smarty cannot be found.

Register a new user using the web interface. Don't worry about configuring your SMTP
server. After creating your user from the web interface, use a tool such as PHPMyAdmin
to change that user's role to 'root', and set their 'active' value to 1. You can delete
the relevant row in the 'verifications' table.
