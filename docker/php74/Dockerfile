FROM php:7.4-apache

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libicu-dev \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    unzip \
    git \
    mariadb-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        mysqli \
        pdo_mysql \
        zip \
        soap \
        exif \
        opcache \
        mbstring \
        curl \
    && a2enmod rewrite headers expires

RUN printf "%s\n" \
    "max_input_vars=5000" \
    "upload_max_filesize=128M" \
    "post_max_size=128M" \
    "memory_limit=512M" \
    "max_execution_time=300" \
    > /usr/local/etc/php/conf.d/moodle.ini
