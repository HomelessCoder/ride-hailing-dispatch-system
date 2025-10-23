FROM registry.fedoraproject.org/fedora-minimal:42

# Base
RUN dnf install -y \
    awk \
    tar \
    zip \
    gzip \
    git \
    make \
    openssl \
    composer \
    iputils \
    --nodocs \
    --setopt install_weak_deps=0

# PHP
RUN dnf install -y \
    php \
    php-devel \
    php-cli \
    php-common \
    php-intl \
    php-mbstring \
    php-opcache \
    php-pdo \
    php-pgsql \
    php-pcntl \
    php-process \
    php-sodium \
    php-xml \
    php-xdebug \
    php-gmp \
    --nodocs \
    --setopt install_weak_deps=0

RUN dnf install postgis -y \
    --nodocs \
    --setopt install_weak_deps=0

# Cleanup space
RUN dnf clean all -y

# Note: Application files are mounted via docker-compose volume
# This allows for live code updates without rebuilding the image

COPY . /app

# Set working directory
WORKDIR /app

RUN composer install --no-dev --optimize-autoloader

# Default command (can be overridden in docker-compose.yml)
CMD ["php", "/app/bin/console", "trade:charge-funds:worker"]
