FROM ubuntu:22.04 AS dev

# Helper scripts
COPY ./.docker/scripts/apt-install-min.sh /usr/local/bin/apt-install-min
RUN chmod +x /usr/local/bin/apt-install-min

# Install APT dependencies
ENV DEBIAN_FRONTEND=noninteractive
RUN apt-install-min \
        apt-transport-https \
        ca-certificates \
        curl \
        default-mysql-client \
        gnupg \
        gpg \
        software-properties-common \
        supervisor \
        unzip \
        wget \
        zip

# Install nginx
RUN echo "deb [signed-by=/usr/share/keyrings/nginx.gpg] https://nginx.org/packages/ubuntu/ $(. /etc/os-release && echo ${VERSION_CODENAME}) nginx" | tee /etc/apt/sources.list.d/nginx.list \
    && wget -O- https://nginx.org/packages/keys/nginx_signing.key | gpg --dearmor | tee '/usr/share/keyrings/nginx.gpg' >/dev/null \
    && apt-install-min nginx \
    # forward nginx logs to docker log collector
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

# Install PHP and extensions
ENV PHP_MINOR=8.1
ENV PHP_INI_DIR=/etc/php/${PHP_MINOR}/fpm
RUN add-apt-repository ppa:ondrej/php \
    && apt-install-min \
        php${PHP_MINOR} \
        php${PHP_MINOR}-curl \
        php${PHP_MINOR}-fpm \
        php${PHP_MINOR}-gd \
        php${PHP_MINOR}-mbstring \
        php${PHP_MINOR}-mysql \
        php${PHP_MINOR}-opcache \
        php${PHP_MINOR}-simplexml \
        php${PHP_MINOR}-zip \
    # We are only supporting one version of PHP, so let's symlink the unversioned `php-fpm` command into our path
    && ln -s /usr/sbin/php-fpm${PHP_MINOR} /usr/sbin/php-fpm \
    # We still must support the pre-7.4 PHP images that look for php-fpm in the local/sbin
    && ln -s /usr/sbin/php-fpm${PHP_MINOR} /usr/local/sbin/php-fpm

# Apply PHP Defaults
ENV PHP_OPCACHE_ENABLE=0 \
    PHP_OPCACHE_MEMORY_CONSUMPTION=64 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=2000 \
    PHP_OPCACHE_REVALIDATE_FREQ=2 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=1 \
    PHP_OPCACHE_INTERNED_STRINGS_BUFFER=4 \
    PHP_OPCACHE_FAST_SHUTDOWN=0 \
    PHP_OPCACHE_BLACKLIST_FILENAME="" \
    PHP_UPLOAD_MAX_FILESIZE=5G \
    PHP_POST_MAX_SIZE=5G \
    PHP_LOG_ERRORS=On \
    PHP_ERROR_LOG=/dev/stderr \
    PHP_SESSION_COOKIE_HTTPONLY=0 \
    PHP_SESSION_COOKIE_SECURE=0 \
    PHP_SHORT_OPEN_TAG=On \
    PHP_EXPOSE_PHP=Off \
    \
    FPM_PM=dynamic \
    FPM_PM_MAX_CHILDREN=50 \
    FPM_PM_START_SERVERS=4 \
    FPM_PM_MIN_SPARE_SERVERS=4 \
    FPM_PM_MAX_SPARE_SERVERS=8 \
    FPM_PM_MAX_REQUESTS=0

# Install development APT dependencies
RUN apt-install-min \
        dirmngr \
        dos2unix \
        git \
        g++ \
        jq \
        make \
        openssh-client \
        php${PHP_MINOR}-phpdbg \
        php${PHP_MINOR}-sqlite3 \
        python2 \
        python3 \
        rsync \
        sqlite3 \
        vim

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_VENDOR_DIR=/var/www/app/vendor \
    COMPOSER_HOME=/composer \
    PATH=/var/www/app/vendor/bin:/composer/vendor/bin:$PATH

# Install and preconfigure XDebug
ENV XDEBUG_ENABLED=0 \
    XDEBUG_AUTOSTART=off \
    XDEBUG_CONF_FILE=docker-php-ext-xdebug.ini \
    XDEBUG_CONNECT_BACK_PORT=9000 \
    XDEBUG_CONNECT_BACK=0 \
    XDEBUG_REMOTE_HOST=localhost \
    XDEBUG_REMOTE_LOG=/var/www/app/storage/logs/xdebug.log \
    FASTCGI_READ_TIMEOUT=60s
RUN apt-install-min php${PHP_MINOR}-xdebug \
    && { \
        echo ""; \
        echo "[xdebug]"; \
        echo "xdebug.remote_enable = ${XDEBUG_ENABLED}"; \
        echo "xdebug.remote_autostart = ${XDEBUG_AUTOSTART}"; \
        echo "xdebug.remote_connect_back = ${XDEBUG_CONNECT_BACK}"; \
        echo "xdebug.remote_host = ${XDEBUG_REMOTE_HOST}"; \
        echo "xdebug.remote_port = ${XDEBUG_CONNECT_BACK_PORT}"; \
        echo "xdebug.remote_log = ${XDEBUG_REMOTE_LOG}"; \
        echo "xdebug.remote_handler = dbgp"; \
        echo "xdebug.max_nesting_level = 1000"; \
    } >> "${PHP_INI_DIR}/../mods-available/${XDEBUG_CONF_FILE}"

# Install Node with Yarn
ENV NPM_CONFIG_LOGLEVEL=info \
    NODE_ENV=develop \
    NODE_PATH=/var/www/app/node_modules \
    PATH=/var/www/app/node_modules/.bin:$PATH
RUN wget -O- https://deb.nodesource.com/setup_lts.x | bash - \
    && echo 'deb [signed-by=/usr/share/keyrings/yarnkey.gpg] https://dl.yarnpkg.com/debian stable main' | tee /etc/apt/sources.list.d/yarn.list \
    && wget -O- https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor | tee /usr/share/keyrings/yarnkey.gpg >/dev/null \
    && apt-install-min nodejs yarn

# Helper scripts for xdebug
#COPY ./.docker/config/enable-debugger.sh  /usr/local/bin/enable-debugger
#RUN chmod +x /usr/local/bin/enable-debugger

# Copy in various config/ini files
COPY ./.docker/config/php.app.ini /etc/php/${PHP_MINOR}/cli/conf.d/app.ini
COPY ./.docker/config/php.app.ini /etc/php/${PHP_MINOR}/fpm/conf.d/app.ini
COPY ./.docker/config/php-fpm.conf /etc/php/${PHP_MINOR}/fpm/php-fpm.conf
COPY ./.docker/config/supervisord.conf /etc/supervisor/supervisord.conf

# Copy the nginx configuration files.
COPY ./.docker/config/nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./.docker/config/nginx/nginx-site-dev.conf /etc/nginx/servers/default.conf

WORKDIR "/var/www/app"
# Copy in package code as late as possible, as it changes the most
COPY --chown=www-data:www-data . .

EXPOSE 80 443

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
