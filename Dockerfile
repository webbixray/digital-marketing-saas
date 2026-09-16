# Minimal staging Dockerfile
# Uses official PHP-FPM Alpine + Redis extension only
# All PHP/Node dependencies are mounted from host via docker-compose volumes

FROM php:8.4-fpm-alpine

# Install Redis extension (small package, cached layer)
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS pcre-dev

# Expose PHP-FPM
EXPOSE 9000
