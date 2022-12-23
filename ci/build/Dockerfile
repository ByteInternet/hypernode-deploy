# Build args
ARG NODE_VERSION
ARG PHP_VERSION

# Ref node image for later copy usage
FROM node:${NODE_VERSION}-buster as node-image

# Install hypernode-deploy dependencies with target PHP version
FROM php:${PHP_VERSION}-cli-buster as build-vendor

COPY ./src /hypernode/src
COPY ./composer.json /hypernode/composer.json

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git zip unzip \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer \
    && composer install --no-dev --optimize-autoloader --working-dir=/hypernode

# Compile hipex-deploy PHAR binary with box using PHP 8.1
FROM php:8.1-cli-buster as build

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        zip \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# We can't use .dockerignore here.
COPY ./src /hypernode/src
COPY ./ci/compile.sh /hypernode/ci/compile.sh
COPY ./.git /hypernode/.git
COPY ./build /hypernode/build
COPY ./bin /hypernode/bin
COPY ./box.json /hypernode/box.json
COPY ./composer.json /hypernode/composer.json
COPY ./ci/build/files /
COPY --from=build-vendor /hypernode/vendor /hypernode/vendor

# Install composer
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer && chmod +x /usr/local/bin/composer

RUN sh /hypernode/ci/compile.sh

# Finish the rest of the image build
FROM php:${PHP_VERSION}-cli-buster

# Add docker repository
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        apt-transport-https \
        ca-certificates \
        curl \
        gnupg-agent \
        gnupg \
        software-properties-common \
    && rm -rf /var/lib/apt/lists/* \
    && curl -fsSL https://download.docker.com/linux/debian/gpg | apt-key add - \
    && add-apt-repository \
          "deb [arch=amd64] https://download.docker.com/linux/debian \
          $(lsb_release -cs) \
          stable"

# Install dependencies
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        openssh-client \
        rsync \
        git \
        patch \
        bash \
        ca-certificates \
        python3 \
        wget \
        curl \
        openssl \
        g++ \
        autoconf \
        make \
        libtool \
        docker \
        gnupg \
        zip \
        docker-ce-cli \
        bc \
    && rm -rf /var/lib/apt/lists/*

# Install php extensions
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/

RUN install-php-extensions \
        pcntl \
        dom \
        bcmath \
        gd \
        intl \
        ldap \
        mbstring \
        mysqli \
        opcache \
        pdo_mysql \
        soap \
        xsl \
        zip \
        pcntl \
        sockets \
        yaml \
        imagick \
        calendar

# Install solr for PHP < 8.1, because solr hasn't been made available for 8.1 on pecl.
# Source: https://github.com/mlocati/docker-php-extension-installer/issues/549
RUN export PHP_VERSION_MAJOR_MINOR=$(echo ${PHP_VERSION} | cut -d'.' -f1,2) && \
    export PHP_VERSION_SOLR_COMPAT_COMMAND="$(echo "${PHP_VERSION_MAJOR_MINOR} < 8.1")" && \
    export PHP_VERSION_SOLR_COMPAT_RESULT=$(echo "${PHP_VERSION_SOLR_COMPAT_COMMAND}" | bc -l) && \
    if [ "${PHP_VERSION_SOLR_COMPAT_RESULT}" -eq "1" ]; then install-php-extensions solr; else echo "not installing solr"; fi

# Install node
COPY --from=node-image /opt /opt
COPY --from=node-image /usr/local/lib /usr/local/lib
COPY --from=node-image /usr/local/bin /usr/local/bin

# Install composer 1
RUN curl -sS https://getcomposer.org/installer | php -- --1 --filename=composer1 && mv composer1 /usr/local/bin/ && chmod +x /usr/local/bin/composer1
# Install composer 2
RUN curl -sS https://getcomposer.org/installer | php -- --2 --filename=composer2 && mv composer2 /usr/local/bin/ && chmod +x /usr/local/bin/composer2
# Use version 1 for main composer binary
RUN ln -s /usr/local/bin/composer1 /usr/local/bin/composer

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

# Setup hipex deploy command
COPY --from=build /hypernode/build/hypernode-deploy.phar /bin/hypernode-deploy
RUN chmod +x /bin/hypernode-deploy
RUN ln -s /bin/hypernode-deploy /bin/hipex-deploy

# Setup default command
CMD ["hypernode-deploy"]

# Setup build location
RUN mkdir /build
VOLUME /build
WORKDIR /build
