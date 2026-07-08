# syntax=docker/dockerfile:1

FROM php:8.4-cli AS base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl libpq-dev libicu-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl zip opcache \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/app.ini /usr/local/etc/php/conf.d/app.ini
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
EXPOSE 8000


FROM base AS dev

# Chromium + son driver depuis les dépôts Debian : disponibles en amd64 ET arm64
# (x86_64, Apple Silicon, ARM…), avec navigateur et driver de versions compatibles.
RUN apt-get update && apt-get install -y --no-install-recommends \
        chromium chromium-driver \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# chromium-driver fournit /usr/bin/chromedriver, que Panther trouve seul via le PATH.
ENV PANTHER_NO_SANDBOX=1 \
    PANTHER_CHROME_BINARY=/usr/bin/chromium \
    PANTHER_CHROME_ARGUMENTS=--disable-dev-shm-usage

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

CMD ["entrypoint.sh"]
