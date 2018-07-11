#!/usr/bin/env bash


if [[ $(mysqlshow -h "$MYSQL_HOST" -u root -p$MYSQL_ROOT_PASSWORD "$MYSQL_DATABASE" | grep "v3_") ]]; then
    echo "Database already initialized, skipping."
    exit 0
fi

echo "CREATE USER '$MYSQL_USER'@'%' IDENTIFIED BY '$MYSQL_USER_PASSWORD'" | mysql -h "$MYSQL_HOST" -u root -p$MYSQL_ROOT_PASSWORD
echo "GRANT ALL PRIVILEGES ON stk_addons.* TO 'stk_addons'@'%'" | mysql -h "$MYSQL_HOST" -u root -p$MYSQL_ROOT_PASSWORD

mysql -h "$MYSQL_HOST" -u root -p$MYSQL_ROOT_PASSWORD "$MYSQL_DATABASE" < /docker-tools/install.sql
