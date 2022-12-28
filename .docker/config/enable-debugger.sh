#!/bin/bash
#

# should xdebug be turned on?
if [ "$XDEBUG_ENABLED" -gt 0 ] && [ -f "${PHP_INI_DIR}/../mods-available/${XDEBUG_CONF_FILE}" ]
then
    # change xdebug configuration, replace default values inline with env vars
    sed -i \
        -e "s|xdebug.remote_enable =.*|xdebug.remote_enable = 1|" \
        -e "s|xdebug.remote_autostart =.*|xdebug.remote_autostart = $XDEBUG_AUTOSTART|" \
        -e "s|xdebug.remote_port =.*|xdebug.remote_port = $XDEBUG_CONNECT_BACK_PORT|" \
        -e "s|xdebug.remote_connect_back =.*|xdebug.remote_connect_back = $XDEBUG_CONNECT_BACK|" \
        -e "s|xdebug.remote_host =.*|xdebug.remote_host = \"$XDEBUG_REMOTE_HOST\"|" \
        -e "s|xdebug.remote_log =.*|xdebug.remote_log = \"$XDEBUG_REMOTE_LOG\"|" \
        "${PHP_INI_DIR}/../mods-available/${XDEBUG_CONF_FILE}"

    ln -s "${PHP_INI_DIR}/../mods-available/${XDEBUG_CONF_FILE}" "${PHP_INI_DIR}/conf.d/docker-php-ext-xdebug.ini"

    # add or change nginx timeout to lengthen script runtime for debugging
    grep -q fastcgi_read_timeout /etc/nginx/nginx.conf &&
        # replace timeout inline if already exist
        sed -i "s|fastcgi_read_timeout .*;|fastcgi_read_timeout $FASTCGI_READ_TIMEOUT;|" /etc/nginx/nginx.conf ||

        # OR if missing from config, append new line with timeout
        sed -i "/include \/etc\/nginx\/conf\.d\/.*/ a\\ fastcgi_read_timeout $FASTCGI_READ_TIMEOUT;" /etc/nginx/nginx.conf

    echo "Enabled XDebug."
fi
