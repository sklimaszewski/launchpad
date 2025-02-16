#!/usr/bin/env bash

cd $PROJECTMAPPINGFOLDER

DATA_FOLDER_NAME=${DATA_FOLDER_NAME:-data}
DUMP_DIR="$(pwd)/${DATA_FOLDER_NAME}"
if [ "$1" != "" ] && [ -d "$1" ]; then
    if [[ "$1" =~ ^/ ]]; then
        DUMP_DIR="$1"
    fi
fi

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
        DATABASE_PORT_VAR=PORT
        DATABASE_USER_VAR=USER
        DATABASE_PASSWORD_VAR=PASS
    else
        PROTOCOL="mysql"
        DATABASE_NAME_VAR=${prefix}_NAME
        DATABASE_HOST_VAR=${prefix}_HOST
        DATABASE_PORT_VAR=3306
        DATABASE_USER_VAR=${prefix}_USER
        DATABASE_PASSWORD_VAR=${prefix}_PASSWORD
    fi

    DB_FILE_NAME="${!DATABASE_NAME_VAR}"

    case "${PROTOCOL}" in
        mysql)
            # Wait for the DB
            while ! mysqladmin ping -h"${!DATABASE_HOST_VAR}" -u"${!DATABASE_USER_VAR}" -p"${!DATABASE_PASSWORD_VAR}" --silent; do
                echo -n "."
                sleep 1
            done
            echo ""

            DB_FILE_PATH="$DUMP_DIR/$DB_FILE_NAME.sql.gz"
            MYSQL="mysql -h${!DATABASE_HOST_VAR} -u${!DATABASE_USER_VAR} -p${!DATABASE_PASSWORD_VAR}"

            echo "Importing ${!DATABASE_NAME_VAR} database."
            zcat $DB_FILE_PATH | sed '1{/999999.*sandbox/d}' | $MYSQL ${!DATABASE_NAME_VAR}
            echo "${!DATABASE_NAME_VAR} database imported."
        ;;
        mongodb)
            DB_FILE_PATH="$DUMP_DIR/$DB_FILE_NAME.gz"

            echo "Importing ${!DATABASE_NAME_VAR} database."
            mongorestore --uri=${!DATABASE_URL_VAR} --drop --gzip --archive=${DB_FILE_PATH}
            echo "${!DATABASE_NAME_VAR} database imported."
        ;;
        postgresql)
            # Wait for the DB
            while ! pg_isready -h ${!DATABASE_HOST_VAR} -p ${!DATABASE_PORT_VAR} > /dev/null 2> /dev/null; do
                echo -n "."
                sleep 1
            done
            echo ""

            echo "Importing ${!DATABASE_NAME_VAR} database."
            zcat $DUMP_DIR/$DB_FILE_NAME.tar.gz | PGPASSWORD="${!DATABASE_PASSWORD_VAR}" pg_restore -c -h ${!DATABASE_HOST_VAR} -p ${!DATABASE_PORT_VAR} -U ${!DATABASE_USER_VAR} -F c -d ${!DATABASE_NAME_VAR}
            echo "${!DATABASE_NAME_VAR} database imported."
        ;;
        *)
            echo "Unknown protocol '${PROTOCOL}'\n" >&2
            exit 1
        ;;
    esac
done

for arg in "$@"
do
    STORAGE_NAME="$(echo $arg | grep = | cut -d= -f1)"
    STORAGE_PATH=$(echo $arg | sed -e s,$STORAGE_NAME=,,g)
    STORAGE_FILE_PATH="$DUMP_DIR/$STORAGE_NAME.tar.gz"

    if [ -f $STORAGE_FILE_PATH ]; then
        if [ -d "${STORAGE_PATH}" ]; then
            rm -rf $STORAGE_PATH
        fi

        STORAGE_DIRNAME=$(dirname $STORAGE_PATH)

        tar xvzf $STORAGE_FILE_PATH -C $STORAGE_DIRNAME/

        echo "Storage imported to ${STORAGE_DIRNAME}/ from ${STORAGE_NAME}.tar.gz"
    fi
done