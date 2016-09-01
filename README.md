# STK Addons Website
This is the source code for the SuperTuxKart asset sharing and distribution
platform. The official location of the production website is http://addons.supertuxkart.net/.

## Build Status
[![Build Status](https://travis-ci.org/leyyin/stk-addons.svg?branch=master)](https://travis-ci.org/leyyin/stk-addons)

## Installation
This software has no automated installation mechanism as it is not intended for wide
usage. However, you can manually install all dependencies, database tables and
settings.

### Dependencies
First of all, you need a the apache webserver with PHP 5 and MySQL server v5.5.3+.
Most features will also work with other webservers, but there is no support for them.

To get a list of the additional PHP packages needed, point your web browser to the `install/` directory.
The script file located there (index.php) will show which dependencies are missing.

Install all package dependencies in a Debian system with the following command:
```
sudo apt-get install php php-mysql mysql-server apache2 php-mcrypt php-mbstring php-gd php-pdo php-zip php-gettext
```

PHP dependencies are handled by [composer](https://getcomposer.org/) (install it if you do not have it already).
If you try to run `composer install` and you have unmet PHP extension dependencies
(the script from the install directory should give an overview of all missing PHP extensions), then composer will fail.
All the steps below take place in a shell in the root of the project.

If you want to install all the dependencies including the developer ones (testing framework):

    composer install

To update dependencies afterwards:

    composer update


If you want to install it in a production environment (no developer dependencies),
just append the `--no-dev --optimize-autoloader` options:

    composer install --no-dev --optimize-autoloader
    or
    composer update --no-dev --optimize-autoloader


JavaScript/CSS dependencies are managed by [bower](http://bower.io/) (install it if you do not have it already).

To install dependencies with bower:

    bower install

To update the dependencies:

    bower update

If you are running in a production environment, just append the `--production` option:

    bower install --production
    or
    bower update --production


### Database
Currently we only support MySQL as a database backend. A newer version `v5.5.3+` is required to have proper unicode support.

You can generate the database (name it as you wish) using a tool like [phpMyAdmin](http://www.phpmyadmin.net/home_page/index.php) or with the mysql shell.
Import the [install.sql](install/install.sql) file found in the repository (in the `install` directory).

Register a new user using the web interface. Don't worry about configuring your email settings.
After creating your user, you can change that user's `role_id` to be 3 (see `roles` tables for other role id's), and  'is_active' value to 1.
You can delete the relevant row in the 'verifications' table. You can login now with your new user.

#### Instructions for MySQL shell
Add a new user with full access to the new database and import `install/install.sql` with ```use DATABASE_NAME; source install/install.sql;``` inside the MySQL shell
or with ```mysql -u root -p -h DATABASE_HOST DATABASE_NAME < install/install.sql``` in a normal shell.

### Finish
Copy the `install/config.EXAMPLE.php` to the root of the project and rename it to `config.php`.

Change the `$ROOT_LOCATION` variable to match the location of your website. Otherwise, JavaScript and CSS will not work.

Setting `DEBUG_MODE` to `true` can help you debugging by showing additional information. You should disable it in productive use.

Change the database settings according to your configuration, then go the project root and check if it works.


### API (optional)
The API is required for in-game access to the add-on system. It only works if URL rewriting is enabled (see below).
In the default configuration, the API resides in a subfolder of the website (`/api`), but on the production STK Addons server, it's in a sub-domain (`api.stkaddons.net`).

### URL Rewriting (optional)
We make heavy use of URL rewriting (the download statistics, the API, nice URL paths). Make sure that `mod_rewrite` is installed and enabled.

If you want to enable download statistics and nice URL paths just copy `install/htaccess.example` file to the root of the project
and rename it to `.htaccess`. You may have to change some paths around to make everything work, the rewrite file assumes the project is in root
of the website. (e.g. if your project is in `localhost/stkaddons`, the rewrite file won't work)

## Common Problems

### Permissions
A common problem on Linux are the permissions for the `assets/cache` and `dl` directories.
There are several ways to solve this problem:
* Change the permission of the directories with `chmod 775` (not recommended)
* Add yourself to the owner group of these directories and give the group read & write access, or change the owner of those directories
to the user under which your webserver is running (usually www-data). The latter can be achieved using:
```sudo chown -R www-data:www-data <directory>```

### Missing extension after install
Sometimes even after you install `mcrypt` extension for PHP it tells you that it is disabled or not available.
The solution is to enable it: `sudo php5enmod mcrypt && sudo service apache2 restart`

### Bower doesn't work
If ```bower --version``` doesn't give any output, it hasn't found the nodejs installation. You can fix that with
```ln -s /usr/bin/nodejs /usr/bin/node```

## Testing
The project uses [PHPUnit](http://phpunit.de/) for unit testing (it's installed automatically by composer if you have enabled the developer dependencies)

Run tests from the root of the project with (it will use the default `phpunit.xml` found in the root directory):

    ./vendor/bin/phpunit

If you want to give it a custom configuration use the `--configuration` flag, like this:

    ./vendor/bin/phpunit --configuration custom.xml

## Translation and locales generation
To generated all locales supported, run the script in `[locale/locale-gen.sh](locale/locale-gen.sh)`.

After that, update the`translations.pot` files by running the `[locale/update-pot.sh](locale/update-pot.sh)` script.

Then after getting the updated translate `po` files from https://www.transifex.com/supertuxkart/supertuxkart/ run the
`[locale/generate-mo-pot.sh](locale/generate-mo-pot.sh)` script.

## Contributing
All contributions are welcome: ideas, patches, documentation, bug reports, complaints, etc!

The PHP coding standard is heavily based on [PSR-2](http://www.php-fig.org/psr/psr-2/), with some modifications:
* The line limit is 120 characters.
* Opening braces for control structures MUST go on the next line, and closing braces MUST go on the next line after the body.
```php
if ($a === 42)
{
    bar();
}
else
{
    foo();
}
```

For JavaScript, CSS, and SQL you should use 4 spaces, not tabs.
The JavaScript coding standard is based on http://javascript.crockford.com/code.html and the
CSS coding standard is based on http://make.wordpress.org/core/handbook/coding-standards/css/.

The JavaScript and CSS coding standards are modified to use the same line limit as PHP.

## License
STK Addons Website is licensed under GPL version 3. See [COPYING](COPYING) for the full license text.

## Contact
* Mailing list: [supertuxkart-devel at SourceForge](http://sourceforge.net/p/supertuxkart/mailman/supertuxkart-devel/)
* Forum: [at FreeGameDev Forums](http://forum.freegamedev.net/viewforum.php?f=16)
* IRC: [#supertuxkart on Freenode](https://webchat.freenode.net/?channels=#supertuxkart)
* Twitter: [@supertuxkart](https://twitter.com/supertuxkart)

