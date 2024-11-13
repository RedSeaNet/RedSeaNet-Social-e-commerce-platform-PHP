#https://hub.docker.com/r/arm64v8/php/
FROM php:8.1.28-fpm
ARG timezone
ENV TIMEZONE=${timezone:-"Asia/Shanghai"}
RUN groupadd -g 503 www
RUN useradd -g www -u 503 -s /sbin/nologin -d /dev/null -M www 
RUN apt-get upgrade -y
RUN apt-get update -y && apt-get install -y \
        g++ \
        git \
        zip \
        vim \
        autoconf \
        bash \ 
        libxml2-dev \
        libcurl3-dev \
        pkg-config \
        libjpeg62-turbo-dev \
        libicu-dev \
        libsodium-dev \
        libzip-dev \
        libwebp-dev \
        libevent-dev \
        libssl-dev \
        libonig-dev \
        apt-transport-https \
        libpq-dev \
        graphviz \
        build-essential \
        python3-pip \
        unzip \
        rsyslog \
        cron \
        python3 \
        zlib1g-dev \
        sudo \
        wget \
        dialog \
        nano \
        libreadline-dev \
        libbz2-dev \
        openssl \
        libssl-dev \
        procps \
        htop
RUN apt install -y libmagickwand-dev --no-install-recommends
RUN apt-get upgrade -y && apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev libjpeg-dev libwebp-dev
RUN docker-php-ext-install -j$(nproc) pdo  
RUN docker-php-ext-install -j$(nproc) sockets
RUN docker-php-ext-install -j$(nproc) pdo_mysql
RUN docker-php-ext-install -j$(nproc) soap 
RUN docker-php-ext-install -j$(nproc) mysqli
RUN docker-php-ext-install -j$(nproc) pdo_pgsql
RUN docker-php-ext-install -j$(nproc) pgsql
#RUN docker-php-ext-install -j$(nproc) openssl
RUN docker-php-ext-install -j$(nproc) iconv
RUN docker-php-ext-install -j$(nproc) pcntl
RUN docker-php-ext-install -j$(nproc) sodium
RUN docker-php-ext-install -j$(nproc) shmop sysvmsg sysvsem sysvshm
RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install -j$(nproc) gd
RUN pecl install -o -f redis &&  rm -rf /tmp/pear &&  docker-php-ext-enable redis
RUN apt-get -y install libicu-dev
RUN docker-php-ext-install -j$(nproc) intl
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install bz2 bcmath calendar
RUN docker-php-ext-install -j$(nproc) opcache
RUN apt-get -y install zlib1g-dev
RUN docker-php-ext-install zip
RUN apt-get update && apt-get -y install libmcrypt-dev mcrypt
RUN pecl install mcrypt && docker-php-ext-enable mcrypt
RUN pecl install -o -f event && docker-php-ext-enable event && docker-php-ext-install posix
RUN pecl install imagick && docker-php-ext-enable imagick
RUN apt-get update && apt-get -y install ffmpeg
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
#RUN pecl install swoole && docker-php-ext-enable swoole
RUN pecl install mongodb && docker-php-ext-enable mongodb
CMD ["php", "/var/www/html/store.redseanet.com/cli/RabbitMp.php"]