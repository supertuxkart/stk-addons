FROM ubuntu:18.04

RUN export DEBIAN_FRONTEND=noninteractive && ln -fs /usr/share/zoneinfo/America/New_York /etc/localtime

RUN apt update && \
    apt -y install software-properties-common apt-utils curl gnupg cron git-core && \
    add-apt-repository -y ppa:ondrej/php && \
    add-apt-repository -y ppa:ondrej/apache2
RUN apt update && apt -y install \
        apache2 \
        php7.2 \
        mod-php7.2 \
        php7.2-curl \
        php7.2-mbstring \
        php7.2-gd \
        php7.2-gettext \
        php7.2-pdo \
        php7.2-pdo-mysql \
        php7.2-simplexml \
        php7.2-zip

# install nodejs
RUN curl -sL https://deb.nodesource.com/setup_10.x | bash - &&
RUN apt update && apt -y install nodejs

# install yarn
RUN curl -sL https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN apt update && apt -y install yarn


# install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer global require "hirak/prestissimo:^0.3" --no-suggest --no-progress

# install cron
RUN \
    # configure cron tasks
    echo '0 * * * * php /var/www/stk-addons/cron/hourly.php >/dev/null 2>&1' > /etc/cron.d/hourly && \
    echo '0 2 * * * php /var/www/stk-addons/cron/daily.php >/dev/null 2>&1' > /etc/cron.d/daily && \
    echo '0 2 */7 * * php /var/www/stk-addons/cron/weekly.php >/dev/null 2>&1' > /etc/cron.d/weekly

# move configuration from install directory to specific directories
COPY ./install/apache.EXAMPLE.conf /etc/apache2/sites-enabled/stk-addons.conf
COPY ./install/htaccess.EXAMPLE /var/www/stk-addons/.htaccess
COPY ./install/config.EXAMPLE.php /var/www/stk-addons/config.php

RUN rm /etc/apache2/sites-enabled/000-default.conf
RUN cp /etc/apache2/mods-available/rewrite.* /etc/apache2/mods-enabled/

# switch to document root
WORKDIR /var/www/stk-addons

# copy sources to document root
COPY ./ ./

# owner of document root is apache user
RUN chown -R www-data .

# install composer packages
RUN composer install --no-suggest --no-progress

# install bower packages
RUN yarn install --allow-root

# remove unnecesary directories
RUN rm -rf install
RUN rm -rf packages.json yarn.lock .yarnrc .dockerignore .gitattributes .gitignore .travis.yml composer.* docker-compose.* Dockerfile* phpunit.xml

EXPOSE 80

CMD ["systemctl", "start", "cron"]
CMD ["a2enmod", "rewrite"]
CMD ["apachectl", "-DFOREGROUND"]
