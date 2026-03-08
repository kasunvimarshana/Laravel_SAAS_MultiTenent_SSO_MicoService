FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        sockets

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer

# Configure PHP
COPY ./php.ini /usr/local/etc/php/conf.d/custom.ini

# Configure supervisord
COPY ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Configure nginx
COPY ../nginx/nginx.conf /etc/nginx/http.d/default.conf

WORKDIR /var/www/html

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
