# Installation via Docker & docker-compose
We have [Dockerfile](./Dockerfile) and docker-compose files for automated installation of whole website.

Only needed is to run command `docker-composer up` and you are ready to visit `http://localhost:4680/`.

For development purposes we recommend to run
`docker-compose -f docker-compose.yml -f docker-compose.dev.yml up`
which loads additionally docker-compose.dev.yml file, that mounts sources so you do not need to
rebuild container with every change.

# Installation
This software has no automated installation mechanism as it is not intended for wide
usage. However, you can manually install all dependencies, database tables and
settings.

## Dependencies
You need a the apache webserver with PHP `7.2+` (only version tested and supported) and MariaDB server `10.3+`.
Most features will also work with other webservers, but there is no support for them.

To get a list of the additional PHP packages needed, point your web browser to the `install/` directory.
The script file located there (`index.php`) will show which dependencies are missing.

Install all package dependencies in a Ubuntu system with the following commands:
```
# Some prerequisites
sudo apt install apt-transport-https ca-certificates curl software-properties-common gnupg

# First we add some PPAs to have the latest apache and PHP versions
sudo add-apt-repository ppa:ondrej/apache2
sudo add-apt-repository ppa:ondrej/php
# Add the MariaDB version for your distribution https://downloads.mariadb.org/mariadb/repositories/#mirror=nxtHost&distro=Ubuntu
sudo apt-get update
sudo apt-get upgrade

# Install PHP, MariaDB and apache
sudo apt-get install mod-php7.2 \
        php7.2 \
        php7.2-curl \
        php7.2-mbstring \
        php7.2-gd \
        php7.2-gettext \
        php7.2-pdo \
        php7.2-mysql \
        php7.2-xml \
        php7.2-zip
sudo apt-get install mariadb-server apache2

# For email to work
# https://www.digitalocean.com/community/tutorials/how-to-install-and-configure-postfix-as-a-send-only-smtp-server-on-ubuntu-14-04
sudo apt-get install mailutils

# Install composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
sudo composer global require "hirak/prestissimo:^0.3" --no-suggest --no-progress

# Install nodejs https://github.com/nodesource/distributions/blob/master/README.md#debinstall
curl -sL https://deb.nodesource.com/setup_10.x | sudo -E bash -
sudo apt-get update && sudo apt-get install -y nodejs

# Install yarn https://yarnpkg.com/lang/en/docs/install/#debian-stable
curl -sL https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
sudo apt-get update && sudo apt-get install yarn
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

    yarn update latest

If you are running in a production environment, just append the `--production` option:

    yarn install --production



## Database
Currently we only support MariaDB as a database backend. A newer version `v10.3+` is required to have proper unicode support.

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
