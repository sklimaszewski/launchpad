## BASE IMAGE
FROM sklimaszewski/php-symfony:8.1-fpm-mysql as base

WORKDIR /var/www/html/project/symfony

# Setup xdebug
#ENV XDEBUG_ENABLED=0
#RUN mkdir /usr/local/etc/php/enable-xdebug
#COPY docker/dev/symfony/xdebug.ini /usr/local/etc/php/enable-xdebug/99-xdebug.ini

COPY docker/dev/symfony/entrypoint.bash /entrypoint.bash
RUN chmod +x /entrypoint.bash

ENTRYPOINT ["/entrypoint.bash"]
CMD ["php-fpm"]

## BUILD IMAGE
FROM base as build

ENV APP_ENV=prod

# Run composer
COPY symfony/symfony.lock symfony/composer.* ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

# Compile webpack if needed

# Copy project and run post composer scripts
COPY symfony/ ./
RUN composer dump-autoload --no-dev --classmap-authoritative && \
    composer run-script post-install-cmd

ENTRYPOINT ["/entrypoint.bash"]
CMD ["php-fpm"]