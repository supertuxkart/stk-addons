STK Addons Website
==================

This is the source code for the SuperTuxKart asset sharing and distribution
platform. The official location of the operating website is http://www.stkaddons.net/.

Installing Locally
------------------

This software has no automated installation mechanism as it is not intended for wide
usage. However, you can manually generate all of the necessary database tables and
configuration settings manually.

Before you attempt to set up a local installation, you should run `install/check_server_deps.php`
on your web server. This will check to make sure that several dependencies can be found.
There may be other dependencies not tested by that script, but that should be enough to
get started.

Dependencies include:
* PEAR::Mail (run `pear install Mail`)
* PHP's gd module
* PHP's PDO module
* PHP's gettext module

Other dependencies:
* `composer install` to download the php dependencies (for production use `composer install --no-dev --optimize-autoloader`)
* `bower install` to download the javascript and css dependencies

You can generate the database tables, procedures, and relations by using a tool such as
PHPMyAdmin to import the table.sql file found in the repository(in the `install` directory).
You may need to edit the provided SQL file, as it assumes a table prefix of 'v2_' and a
database username of 'stkuser'.

On your web server, you must edit the provided `install/config-base.php` to match your database
and system configuration. Save this file as `config.php` in the website root. Enable the debugging mode in
the configuration file to assist with resolving any errors.

The cache folder is local, if you get permission errors on `assets/cache` just run `chmod 777 assets/cache`.
The same can be said about the `dl/` directory.

Register a new user using the web interface. Don't worry about configuring your SMTP
server. After creating your user from the web interface, use a tool such as PHPMyAdmin
to change that user's role to 'root', and set their 'active' value to 1. You can delete
the relevant row in the 'verifications' table.

The source tree contains an 'api' folder. On the production STK Addons server,
these files exist in a separate sub-domain. For testing on a local machine, you
may wish to copy these files to the parent folder if you intend to test API
functionality. (change the API constants in config.php)

As an optional step you could use the `install/htaccess.example` file to rewrite url's. To do this
move it the website root and rename it to `.htaccess` (be sure that you have installed `mod_rewrite` in apache)

Run tests with `./vendor/bin/phpunit`.
About the Code
--------------

The STKAddons source code tree has grown somewhat organically over the years. There
are many places where the source could be cleaned up.

There are also a number of particularly ugly sections of code which need major
refactoring:
* Addon Upload: This is probably the most convoluted process in the entire system.
  There is a lot of validation that is performed, and many many code paths lumped
  into one hastily written class. I have not yet had the courage to wade through
  this code and fix it. The Upload class could very likely be refactored into several
  smaller classes based on the type of operation being performed.
