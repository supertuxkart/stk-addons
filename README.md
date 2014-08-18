# STK Addons Website
This is the source code for the SuperTuxKart asset sharing and distribution
platform. The official location of the operating website is http://www.stkaddons.net/.

## Build Status
[![Build Status](https://travis-ci.org/leyyin/stkaddons.svg?branch=master)](https://travis-ci.org/leyyin/stkaddons)


## Installing
This software has no automated installation mechanism as it is not intended for wide
usage. However, you can manually install all of the necessary dependencies, database tables and
configure settings manually

### Dependencies
Before you attempt to set up a local installation, you should go in the `install/` directory
on your web server. This will check to make sure that several dependencies can be found.

PHP dependencies are handled by [composer](https://getcomposer.org/) (install it if you do not have it already).
If you try to run `composer install` and you have unmet php extension dependencies
(the script from the install directory should give a overview of all missing php extensions), then composer will fail.

If you want to install all the dependencies including the developer ones (testing framework):

    composer install

To update dependencies afterwards:

    composer update


If you want to install it in a production environment (no developer dependencies),
just append the `--no-dev --optimize-autoloader` options:

    composer install --no-dev --optimize-autoloader
    or
    composer update --no-dev --optimize-autoloader


Javascript/CSS dependencies are managed by [bower](http://bower.io/) (install it if you do not have it already).

To install dependencies with bower:

    bower install

To update the dependencies:

    bower update

If you are running in a production environment, just append the `--production` option:

    bower install --production
    or
    bower update --production


### Database
Currently we only support MySQL for the database backend. A newer version `v5.5.3+` is required, to have proper unicode support.

You can generate the database (name it as you wish) using a tool like [phpMyAdmin](http://www.phpmyadmin.net/home_page/index.php).
Use phpMyAdmin to import the [table.sql](install/table.sql) file found in the repository (in the `install` directory).

Copy the `install/config-base.php` to the root of the project and rename it to `config.php`.

Change the `SITE_ROOT` constant to match the location of your website.(javascript and css will not work if this is not correct)

To enable debug mode just set `DEBUG_MODE` to `true`.(may assist in error debugging).

Fill the database proprieties with the proper info then go the project root and see that it works.

Register a new user using the web interface. Don't worry about configuring your email settings.
After creating your user from the web interface (phpMyAdmin), you can change that user's role to 'admin', and set their 'active' value to 1.
You can delete the relevant row in the 'verifications' table. You can login in now with your new user.

### API (optional)

The source tree contains an 'api' folder. On the production STK Addons server, these files exist in a separate sub-domain.
The default config is setup so that the website api will reside in a directory in localhost (`localhost/stkaddons/api`).
For the API to work, url rewriting must be enabled (see below).

### URL Rewriting (optional)
We make heavy use of url rewriting (the stat downloads, the API, nice url paths). Make sure that `mod_rewrite` is installed and enabled.

If you want to enable stat downloads and nice url paths just copy `install/htaccess.example` file to the root of the project
and rename it to `.htaccess`. You may have to change some paths around to make everything work, the rewrite file asums the the project is in root
of the website. (e.g. you have project may be in directory in `localhost/stkaddons` then the rewrite file will not work)

## Common Problems

A common problem is on linux is permissions for the `assets/cache` or `dl` directories.
To solve this problem there are several solutions:
* change the permission of the directories with `chmod 775` (not recommended)
* add yourself to the the group of these directories, then change the owner of those directories to be your user


## Testing
The project uses [phpunit](http://phpunit.de/) for unit testing (it's installed automatically by composer if you have enabled developer dependencies)

Run tests from the root of the project with (it will use the default `phpunit.xml` found in the root directory):

    ./vendor/bin/phpunit

If you want to give it a custom configuration use the `--configuration` flag, like this:

    ./vendor/bin/phpunit --configuration custom.xml


## License
STK Addons Website is licensed under GPL version 3. See [COPYING](COPYING) for full license text.
