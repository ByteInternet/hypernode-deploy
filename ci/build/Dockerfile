FROM debian:bookworm-slim

ARG NODE_VERSION
ARG PHP_VERSION

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update

RUN apt-get install -y --no-install-recommends \
        git zip unzip wget

# Add repositories
RUN apt-get install -y --no-install-recommends \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg-agent \
    gnupg \
    software-properties-common \
    gettext-base

RUN apt-get clean \
    && curl -fsSL http://debian.hypernode.com/repo.key | gpg --dearmor -o /usr/share/keyrings/hypernode.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/hypernode.gpg] http://debian.hypernode.com bookworm main hypernode" > /etc/apt/sources.list.d/hypernode.list \
    && echo \
        "Package: * \
        Pin origin debian.hypernode.com \
        Pin-Priority: 1000" > /etc/apt/preferences.d/hypernode \
    && apt update

RUN curl -fsSL https://deb.nodesource.com/setup_$NODE_VERSION.x | bash - 

# Install dependencies
RUN apt-get install -y --no-install-recommends \
        openssh-client \
        rsync \
        git \
        patch \
        bash \
        jq \
        ca-certificates \
        python3 \
        virtualenv \
        wget \
        curl \
        openssl \
        g++ \
        autoconf \
        make \
        libtool \
        nodejs \
        gnupg \
        zip \
        bc

# Install PHP
RUN apt-get install -y --no-install-recommends \
        php${PHP_VERSION} \
        php${PHP_VERSION}-amqp \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-bz2  \
        php${PHP_VERSION}-cli  \
        php${PHP_VERSION}-common  \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-igbinary \
        php${PHP_VERSION}-imagick \
        php${PHP_VERSION}-imap \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-ldap \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-odbc \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-pspell \
        php${PHP_VERSION}-readline \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-soap \
        php${PHP_VERSION}-tidy \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-zip

# Confirm NodeJS & NPM are installed
RUN node -v && npm -v

COPY ./.git /hypernode/.git
COPY ./bin /hypernode/bin
COPY ./ci /hypernode/ci
COPY ./src /hypernode/src
COPY ./box.json /hypernode/box.json
COPY ./composer.json /hypernode/composer.json
COPY ./ci/build/files /

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer

# Remove dev dependencies from composer.json
RUN cd /hypernode && \
    jq 'del(.["require-dev"])' composer.json > composer.tmp.json && \
    cp composer.tmp.json composer.json

RUN composer install --no-dev --optimize-autoloader --working-dir=/hypernode --no-scripts --no-progress --no-interaction

RUN bash /hypernode/ci/compile.sh

# Setup hipex deploy command
RUN cp /hypernode/build/hypernode-deploy.phar /bin/hypernode-deploy
RUN chmod +x /bin/hypernode-deploy
RUN ln -s /bin/hypernode-deploy /bin/hipex-deploy

# Install composer 1
RUN curl -sS https://getcomposer.org/installer | php -- --1 --filename=composer1 && mv composer1 /usr/local/bin/ && chmod +x /usr/local/bin/composer1
# Install composer 2
RUN curl -sS https://getcomposer.org/installer | php -- --2.2 --filename=composer2 && mv composer2 /usr/local/bin/ && chmod +x /usr/local/bin/composer2
# Use version 1 for main composer binary
RUN rm -f /usr/local/bin/composer; ln -s /usr/local/bin/composer2 /usr/local/bin/composer

# Set python3 as default python executable
RUN ln -s /usr/bin/python3 /usr/local/bin/python

# Copy container files
COPY ./ci/build/files /

# Setup SSH configuration
RUN mkdir -p /root/.ssh \
    && chmod -vf 700 /root/.ssh \
    && (chmod -vf 600 /root/.ssh/* || true) \
    && chmod -vf 700 /etc/ssh \
    && chmod -vf 600 /etc/ssh/*

# Cleanup
RUN rm -rvf \
        /tmp/* \
        /usr/share/man \
        /var/lib/apt/lists/* \
    && apt-get autoremove -y

# Allow hypernode-deploy to be ran in ordinary git repository locations
RUN git config --global --add safe.directory "*"

# Setup default command
CMD ["hypernode-deploy"]

# Setup build location
RUN mkdir /build
VOLUME /build
WORKDIR /build
