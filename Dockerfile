FROM ubuntu

RUN export DEBIAN_FRONTEND=noninteractive && ln -fs /usr/share/zoneinfo/America/New_York /etc/localtime

RUN apt-get update && apt-get -y install software-properties-common && add-apt-repository -y ppa:ondrej/php
RUN apt-get update && apt-get -y install \
        apache2 \
        mod-php7.1 \
        php7.1-curl \
        php7.1-mbstring \
        php7.1-gd \
        php7.1-gettext \
        php7.1-pdo \
        php7.1-pdo-mysql \
        php7.1-zip

# install npm + bower
RUN apt-get -y install curl gnupg && \
    curl -sL https://deb.nodesource.com/setup_9.x | bash - && \
    apt-get -y install nodejs git-core && npm install -g bower

# install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer global require "hirak/prestissimo:^0.3" --no-suggest --no-progress

# install cron
RUN apt-get -y install cron && \
\
    # configure cron tasks
    echo '0 * * * * php /var/www/stk_addons/hourly.php >/dev/null 2>&1' > /etc/cron.d/hourly && \
    echo '0 2 * * * php /var/www/stk_addons/hourly.php >/dev/null 2>&1' > /etc/cron.d/daily && \
    echo '0 2 */7 * * php /var/www/stk_addons/hourly.php >/dev/null 2>&1' > /etc/cron.d/weekly

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
RUN chown -R www-data . && \
    # install composer packages
    composer install --no-suggest --no-progress --no-dev && \
\
    # install bower packages
    bower install --allow-root && \
\
    # remove install directory
    rm -rf install && \
\
    # remove unnecesary directories
    rm -rf docker_tools .bowerrc .dockerignore .gitattributes .gitignore .travis.yml bower.json composer.* docker-compose.* Dockerfile* phpunit.xml

EXPOSE 80

CMD ["service", "cron", "start"]
CMD ["apachectl", "-DFOREGROUND"]
