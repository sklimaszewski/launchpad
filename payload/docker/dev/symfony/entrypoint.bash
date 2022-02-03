#!/usr/bin/env bash

ORIGPASSWD=$(cat /etc/passwd | grep www-data)
ORIG_UID=$(echo "$ORIGPASSWD" | cut -f3 -d:)
ORIG_GID=$(echo "$ORIGPASSWD" | cut -f4 -d:)
ORIG_HOME=$(echo "$ORIGPASSWD" | cut -f6 -d:)
DEV_UID=${DEV_UID:=$ORIG_UID}
DEV_GID=${DEV_GID:=$ORIG_GID}

if [ "$DEV_UID" -ne "$ORIG_UID" ] || [ "$DEV_GID" -ne "$ORIG_GID" ]; then
    groupmod -g "$DEV_GID" www-data
    usermod -u "$DEV_UID" -g "$DEV_GID" www-data
fi

#cCreate .composer in advance and set the permissions
mkdir -p /var/www/.composer && chown www-data:www-data /var/www/.composer
chown www-data:www-data /var/www/html/project/symfony

# fixing permissions
mkdir -p /var/www/html/project/symfony/var/{cache,logs} && chown www-data:www-data /var/www/html/project/symfony/var/{cache,logs}

# xdebug
if [ "1" = "${XDEBUG_ENABLED}" ]; then
    export PHP_INI_SCAN_DIR=:/usr/local/etc/php/enable-xdebug
fi

exec "$@"
