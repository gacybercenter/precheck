# This is a modified Dockerfile from:
#   https://github.com/librespeed/speedtest
# This is designed to build a custom PreCheck engine
# allowing for a Systems Requirement Check
FROM php:7.4-apache

# Install extensions
RUN apt-get update && apt-get install -y \
    git \
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

COPY --from=speedtest speedtest/backend/ /speedtest/backend

COPY --from=speedtest speedtest/results/*.php /speedtest/results/
COPY --from=speedtest speedtest/results/*.ttf /speedtest/results/

COPY --from=speedtest speedtest/*.js /speedtest/
COPY *.js /speedtest/
COPY *.css /speedtest/
COPY --from=speedtest speedtest/favicon.ico /speedtest/

COPY --from=speedtest speedtest/docker/servers.json /servers.json

COPY --from=speedtest speedtest/docker/*.php /speedtest/
COPY *.php /speedtest/

COPY --from=speedtest speedtest/docker/entrypoint.sh /

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
