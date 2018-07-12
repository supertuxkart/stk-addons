# Installation
This software has no automated installation mechanism as it is not intended for wide
usage. However, you can manually install all dependencies, database tables and
settings.

## Dependencies
You need a the apache webserver with PHP `7.1+` (only version tested and supported) and MySQL server `v5.5.3+`.
Most features will also work with other webservers, but there is no support for them.

To get a list of the additional PHP packages needed, point your web browser to the `install/` directory.
The script file located there (`index.php`) will show which dependencies are missing.

Install all package dependencies in a Debian system with the following command:
```
sudo apt-get install php php-mysql php-mcrypt php-mbstring php-gd php-zip php-gettext
sudo apt-get install mysql-server apache2
```

PHP dependencies are handled by [composer](https://getcomposer.org/) (install it if you do not have it already).
If you try to run `composer install` and you have unmet PHP extension dependencies
(the script from the install directory should give an overview of all missing PHP extensions), then composer will fail.
All the steps below take place in a shell in the root of the project.

If you want to install all the dependencies including the developer ones (testing framework, debug toolbar and other):

    composer install

To update dependencies afterwards:

    composer update


If you want to install it in a production environment (no developer dependencies),
just append the `--no-dev --optimize-autoloader` options:

    composer install --no-dev --optimize-autoloader
    or
    composer update --no-dev --optimize-autoloader


JavaScript/CSS dependencies are managed by [yarn](https://yarnpkg.com/) (install it if you do not have it already).

To install dependencies with yarn:

    yarn install

To update the dependencies:

    yarn update

If you are running in a production environment, just append the `--production` option:

    yarn install --production
    or
    yarn update --production


## Database
Currently we only support MySQL as a database backend. A newer version `v5.5.3+` is required to have proper unicode support.

You can generate the database (name it as you wish) using a tool like [phpMyAdmin](http://www.phpmyadmin.net/home_page/index.php) or with the mysql shell.
Import the [install.sql](install/install.sql) file found in the repository (in the `install` directory).

Register a new user using the web interface. Don't worry about configuring your email settings.
After creating your user, you can change that user's `role_id` to be 3 (see `roles` tables for other role id's), and `is_active` value to `1`.
You can delete the relevant row in the `verifications` table. You can login now with your new user.

### Instructions for MySQL shell
Add a new user with full access to the new database and import `install/install.sql` with ```use DATABASE_NAME; source install/install.sql;``` inside the MySQL shell
or with ```mysql -u root -p -h DATABASE_HOST DATABASE_NAME < install/install.sql``` in a normal shell.

## Finish
Copy the `install/config.EXAMPLE.php` to the root of the project and rename it to `config.php`.

Se the `DOMAIN_NAME` constant to match the location of your website, otherwise JavaScript and CSS will not work.

Setting `DEBUG_MODE` to `true` can help you debugging by showing additional information. You should disable it in the production
environment. 

Change the database settings according to your configuration, then go the project root and check if it works.

If you also want the registration page to work you must change the keys `CAPTCHA_SITE_KEY` and `CAPTCHA_SECRET` in the `config.php`.

Some example Apache and Nginx conf files are inside the `install/` directory, those are configured to make
the instance available locally when going to the url `stk-addons.localhost`.

For the example configs to work properly add the following line `127.0.0.1   stk-addons.localhost` inside
the `/etc/hosts` files (or whatever hosts file your platform uses).

## API (optional)
The API is required for in-game access to the add-on system. It only works if URL rewriting is enabled (see below).
In the default configuration, the API resides in a subfolder of the website (`/api`).

## URL Rewriting (optional)
We make heavy use of URL rewriting (the download statistics, the API, nice URL paths). Make sure that `mod_rewrite` is installed and enabled.

If you want to enable download statistics and nice URL paths just copy `install/htaccess.EXAMPLE` file to the root of the project
and rename it to `.htaccess`. You may have to change some paths around to make everything work, the rewrite file assumes the project is in root
of the website. (e.g. if your project is in `localhost/stkaddons`, the rewrite file won't work)
