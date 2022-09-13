#!/usr/bin/env bash

set -e
set -x

# Handy aliases
HN="ssh app@hndeployintegr8.hypernode.io -o StrictHostKeyChecking=no"
DP="docker run -v /tmp/m2build:/web -e HYPERNODE_API_TOKEN -e SSH_PRIVATE_KEY hndeploy"

# Build Docker image
docker build \
    -f ci/build/Dockerfile \
    --build-arg NODE_VERSION=16 \
    --build-arg PHP_VERSION="${PHP_VERSION:-8.1}" \
    -t hndeploy \
    .

# Copy application from remote to local
$HN /data/web/magento2/bin/magento app:config:dump scopes themes
echo "Waiting for SSH to be available on the Hypernode container"
mkdir /tmp/m2build
mkdir -p "$HOME/.ssh"
cp ci/test/magento/deploy_ephemeral.php /tmp/m2build/deploy.php
rsync -a -e "ssh -o StrictHostKeyChecking=no" app@hndeployintegr8.hypernode.io:magento2/ /tmp/m2build
rm /tmp/m2build/app/etc/env.php

# Build application
$DP hypernode-deploy build -f /web/deploy.php --verbose

# Prepare env
$HN mkdir -p /data/web/apps/banaan.store/shared/app/etc/
$HN cp /data/web/magento2/app/etc/env.php /data/web/apps/banaan.store/shared/app/etc/env.php

##########################################
# DEPLOY WITHOUT PLATFORM CONFIGURATIONS #
# This should pass, but not generate any #
# Nginx/Supervisor/etc configs           #
##########################################
# SSH from deploy container to hypernode container
$DP hypernode-deploy deploy test -f /web/deploy.php -v
