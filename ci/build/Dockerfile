# Build args
ARG NODE_VERSION
ARG PHP_VERSION

# Ref node image for later copy usage
FROM node:${NODE_VERSION}-buster as node-image

# PHP build phase
FROM php:${PHP_VERSION}-cli-buster as build

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        zip \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# We can't use .dockerignore here.
COPY ./src /hipex/src
COPY ./ci/compile.sh /hipex/ci/compile.sh
COPY ./.git /hipex/.git
COPY ./build /hipex/build
COPY ./bin /hipex/bin
COPY ./box.json /hipex/box.json
COPY ./composer.json /hipex/composer.json
COPY ./composer.lock /hipex/composer.lock
COPY ./ci/build/files /

# Install composer
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer && chmod +x /usr/local/bin/composer

RUN sh /hipex/ci/compile.sh

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
    && rm -rf /var/lib/apt/lists/*

# Install php extensions
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions \
        gd \
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
        solr \ 
        calendar

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
COPY --from=build /hipex/build/hipex-deploy.phar /bin/hipex-deploy
RUN chmod +x /bin/hipex-deploy

# Setup default command
CMD ["hipex-deploy"]

# Setup build location
RUN mkdir /build
VOLUME /build
WORKDIR /build
