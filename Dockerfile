# This is a modified Dockerfile from:
#   https://github.com/librespeed/speedtest
# This is designed to build a custom PreCheck engine
# allowing for a Systems Requirement Check
FROM php:7.4-apache

# Install extensions
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libpq-dev \
    && docker-php-ext-install -j$(nproc) iconv \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql pdo_pgsql pgsql

# Prepare files and folders

RUN mkdir -p /speedtest/

# Copy sources

COPY speedtest/backend/ /speedtest/backend

COPY speedtest/results/*.php /speedtest/results/
COPY speedtest/results/*.ttf /speedtest/results/

COPY speedtest/*.js /speedtest/
COPY *.js /speedtest/
COPY *.css /speedtest/
COPY speedtest/favicon.ico /speedtest/

COPY speedtest/docker/servers.json /servers.json

COPY speedtest/docker/*.php /speedtest/
COPY *.php /speedtest/
COPY speedtest/docker/entrypoint.sh /

# Prepare environment variabiles defaults

ENV TITLE=LibreSpeed
ENV MODE=standalone
ENV PASSWORD=password
ENV TELEMETRY=false
ENV ENABLE_ID_OBFUSCATION=false
ENV REDACT_IP_ADDRESSES=false
ENV WEBPORT=80

# Final touches

EXPOSE 80
CMD ["bash", "/entrypoint.sh"]
