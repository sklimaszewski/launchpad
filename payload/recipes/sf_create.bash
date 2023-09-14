#!/usr/bin/env bash

cd $PROJECTMAPPINGFOLDER

PHP="php"
COMPOSER="$PHP -d memory_limit=-1 /usr/local/bin/composer"
PROJECT_FOLDER_NAME=$1

DATABASE_PREFIXES=${DATABASE_PREFIXES:-DATABASE}
for prefix in $DATABASE_PREFIXES
do
    DATABASE_URL_VAR=${prefix}_URL
    if [ -n "${!DATABASE_URL_VAR}" ] ; then
        # Parse DATABASE_URL
        PROTOCOL="$(echo ${!DATABASE_URL_VAR} | grep :// | sed -e's,^\(.*://\).*,\1,g' | cut -d: -f1)"
        URL=$(echo ${!DATABASE_URL_VAR} | sed -e s,"${PROTOCOL}://",,g)
        LOGIN="$(echo $URL | grep @ | cut -d@ -f1)"
        USER="$(echo $LOGIN | grep : | cut -d: -f1)"
        PASS=$(echo $LOGIN | sed -e s,$USER:,,g | cut -d/ -f1)
        HOSTPORT=$(echo $URL | sed -e s,$LOGIN@,,g | cut -d/ -f1)
        HOST="$(echo $HOSTPORT | sed -e 's,:.*,,g')"
        PORT="$(echo $HOSTPORT | sed -e 's,^.*:,:,g' -e 's,.*:\([0-9]*\).*,\1,g' -e 's,[^0-9],,g')"
        NAME="$(echo $URL | grep / | cut -d/ -f2- | grep \? | cut -d\? -f1)"

        DATABASE_NAME_VAR=NAME
        DATABASE_HOST_VAR=HOST
        DATABASE_USER_VAR=USER
        DATABASE_PASSWORD_VAR=PASS
    else
        PROTOCOL="mysql"
        DATABASE_NAME_VAR=${prefix}_NAME
        DATABASE_HOST_VAR=${prefix}_HOST
        DATABASE_USER_VAR=${prefix}_USER
        DATABASE_PASSWORD_VAR=${prefix}_PASSWORD
    fi

    case "${PROTOCOL}" in
        mysql)
            # Wait for the DB
            while ! mysqladmin ping -h"${!DATABASE_HOST_VAR}" -u"${!DATABASE_USER_VAR}" -p"${!DATABASE_PASSWORD_VAR}" --silent; do
                echo -n "."
                sleep 1
            done
            echo ""

            mysql -h"${!DATABASE_HOST_VAR}" -u"${!DATABASE_USER_VAR}" -p"${!DATABASE_PASSWORD_VAR}" -e "CREATE DATABASE ${!DATABASE_NAME_VAR}"
        ;;
        mongodb)
            # Wait for the DB
            sleep 3
            echo ""
        ;;
        *)
            echo "Unknown protocol '${PROTOCOL}'\n" >&2
            exit 1
        ;;
    esac
done

# Install
if [ "$PROJECT_FOLDER_NAME" != "" ]; then
    if [ ! -d symfony ]; then
        echo "Not managed yet."
        exit
    fi

    cd $PROJECT_FOLDER_NAME
fi

$COMPOSER install --no-interaction

echo "Installation OK"

