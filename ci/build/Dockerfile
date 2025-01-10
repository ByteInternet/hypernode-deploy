# Stage 1: Builder
FROM debian:bookworm AS builder

ARG NODE_VERSION
ARG PHP_VERSION

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git zip unzip wget \
    && rm -rf /var/lib/apt/lists/*

# Add repositories
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        apt-transport-https \
        ca-certificates \
        curl \
        gnupg-agent \
        gnupg \
        software-properties-common \
        gettext-base \
    && rm -rf /var/lib/apt/lists/* \
    && curl -sSL http://debian.hypernode.com/repo.key | apt-key add - \
    && echo "deb http://debian.hypernode.com bookworm main hypernode" | tee /etc/apt/sources.list.d/hypernode.list \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource.gpg.key | apt-key add - \
    && echo "deb http://deb.nodesource.com/node_${NODE_VERSION}.x bookworm main" | tee /etc/apt/sources.list.d/nodesource.list \
    && echo \
          "Package: * \
          Pin origin deb.nodesource.com \
          Pin-Priority: 1001" > /etc/apt/preferences.d/nodejs

# Install dependencies
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
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
        bc \
    && apt install -y --no-install-recommends \
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
        php${PHP_VERSION}-opcache \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-pspell \
        php${PHP_VERSION}-readline \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-soap \
        php${PHP_VERSION}-tidy \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-zip \
    && rm -rf /var/lib/apt/lists/*

COPY ./.git /hypernode/.git
COPY ./bin /hypernode/bin
COPY ./ci /hypernode/ci
COPY ./src /hypernode/src
COPY ./box.json /hypernode/box.json
COPY ./composer.json /hypernode/composer.json
COPY ./ci/build/files /

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer \
    && composer install --no-dev --optimize-autoloader --working-dir=/hypernode

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

# Setup build location
RUN mkdir /build
VOLUME /build
WORKDIR /build

# Stage 2: Optimized Runtime
FROM debian:bookworm

ARG PHP_VERSION

ENV DEBIAN_FRONTEND=noninteractive

COPY --from=builder /etc/apt /etc/apt

# Install only necessary runtime dependencies
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        zip \
        unzip \
        wget \
        openssh-client \
        rsync \
        curl \
        nodejs \
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
        php${PHP_VERSION}-opcache \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-pspell \
        php${PHP_VERSION}-readline \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-soap \
        php${PHP_VERSION}-tidy \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-zip \
    && rm -rf /var/lib/apt/lists/*

# Copy necessary files from the builder stage
COPY --from=builder /usr/local/bin/composer /usr/local/bin/composer
COPY --from=builder /bin/hypernode-deploy /bin/hypernode-deploy
COPY --from=builder /hypernode /hypernode

# Copy container files
COPY ./ci/build/files /

# Set python3 as default python executable
RUN ln -s /usr/bin/python3 /usr/local/bin/python

# Setup SSH configuration
RUN mkdir -p /root/.ssh \
    && chmod -vf 700 /root/.ssh \
    && (chmod -vf 600 /root/.ssh/* || true) \
    && chmod -vf 700 /etc/ssh \
    && chmod -vf 600 /etc/ssh/*

# Setup default command
CMD ["hypernode-deploy"]

# Setup build location
RUN mkdir /build
VOLUME /build
WORKDIR /build
