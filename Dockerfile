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

RUN curl -fsSL https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb -o /tmp/chrome.deb \
    && apt-get update \
    && apt-get install -y --no-install-recommends /tmp/chrome.deb \
    && CHROME_VER="$(google-chrome --version | grep -oE '[0-9.]+' | head -1)" \
    && curl -fsSL "https://storage.googleapis.com/chrome-for-testing-public/${CHROME_VER}/linux64/chromedriver-linux64.zip" -o /tmp/chromedriver.zip \
    && unzip -j /tmp/chromedriver.zip 'chromedriver-linux64/chromedriver' -d /usr/local/bin \
    && chmod +x /usr/local/bin/chromedriver \
    && rm -f /tmp/chrome.deb /tmp/chromedriver.zip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

ENV PANTHER_NO_SANDBOX=1 \
    PANTHER_CHROME_BINARY=/usr/bin/google-chrome \
    PANTHER_CHROME_ARGUMENTS=--disable-dev-shm-usage

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

CMD ["entrypoint.sh"]
