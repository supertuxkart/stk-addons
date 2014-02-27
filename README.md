STK Addons Website
==================

This is the source code for the SuperTuxKart asset sharing and distribution
platform. The official location of the operating website is http://www.stkaddons.net/.

Installing Locally
------------------

This software has no automated installation mechanism as it is not intended for wide
usage. However, you can manually generate all of the necessary database tables and
configuration settings manually.

Before you attempt to set up a local installation, you should run `check_server_deps.php`
on your web server. This will check to make sure that several dependencies can be found.
There may be other dependencies not tested by that script, but that should be enough to
get started.

Dependencies include:
* PEAR::Mail
* PHP's gd module
* PHP's PDO module
* PHP's MySQLi module (in the process of removing)
* PHP's gettext module
* Smarty Template Engine

You can generate the database tables, procedures, and relations by using a tool such as
PHPMyAdmin to import the table.sql file found in the repository. You may need to edit
the provided SQL file, as it assumes a table prefix of 'v2_' and a database username of
'stkaddons_stkbase'.

On your web server, you must edit the provided `config-base.php` to match your database
and system configuration. Save this file as `config.php`. Enable the debugging mode in
the configuration file to assist with resolving any errors.

Download Smarty (http://www.smarty.net/) and make sure it is located in your PHP
include path, as directed by the PHP errors that will appear if Smarty cannot be found.
Any 3.1.x version should work.

Register a new user using the web interface. Don't worry about configuring your SMTP
server. After creating your user from the web interface, use a tool such as PHPMyAdmin
to change that user's role to 'root', and set their 'active' value to 1. You can delete
the relevant row in the 'verifications' table.

The source tree contains an 'api' folder. On the production STK Addons server,
these files exist in a separate sub-domain. For testing on a local machine, you
may wish to copy these files to the parent folder if you intend to test API
functionality.

About the Code
--------------

The STKAddons source code tree has grown somewhat organically over the years. There
are many places where the source could be cleaned up.

There are a number of ongoing refactoring projects within the code-base:
* Converting all database calls to use PDO rather than mysqli, through the DBConnection
  class. It is intended to eventually remove `sql.php` completely. Transactions should
  be used where applicable.
* Making use of a template engine for all UI code. The first version of STKAddons had
  html baked right into the PHP code, and this is generally considered bad practice.
  I (Stephen) have been trying to slowly weed that out and move to template files for
  everything, so that we might offer customizable themes or a mobile UI for example.

There are also a number of particularly ugly sections of code which need major
refactoring:
* Addon Upload: This is probably the most convoluted process in the entire system.
  There is a lot of validation that is performed, and many many code paths lumped
  into one hastily written class. I have not yet had the courage to wade through
  this code and fix it. The Upload class could very likely be refactored into several
  smaller classes based on the type of operation being performed.
