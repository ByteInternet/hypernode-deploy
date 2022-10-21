#!/usr/bin/env bash

set -e
set -x

# Handy aliases
HN="ssh app@hndeployintegr8.hypernode.io -o StrictHostKeyChecking=no"
DP="docker run --rm -v /tmp/m2build:/web -e HYPERNODE_API_TOKEN -e SSH_PRIVATE_KEY -w /web hndeploy"

# Build Docker image
docker build \
    -f ci/build/Dockerfile \
    --build-arg NODE_VERSION=16 \
    --build-arg PHP_VERSION="${PHP_VERSION:-8.1}" \
    -t hndeploy \
    .

# Copy application from remote to local
$HN /data/web/magento2/bin/magento app:config:dump scopes themes
mkdir /tmp/m2build
mkdir -p "$HOME/.ssh"
cp ci/test/magento/deploy_brancher.php /tmp/m2build/deploy.php
rsync -a -e "ssh -o StrictHostKeyChecking=no" app@hndeployintegr8.hypernode.io:magento2/ /tmp/m2build
rm /tmp/m2build/app/etc/env.php

# Build application
$DP hypernode-deploy build -f /web/deploy.php -vvv

##########################################
# DEPLOY WITHOUT PLATFORM CONFIGURATIONS #
# This should pass, but not generate any #
# Nginx/Supervisor/etc configs           #
##########################################
# SSH from deploy container to hypernode container
$DP hypernode-deploy deploy test -f /web/deploy.php -vvv

# Run some tests

$DP ls -l
$DP test -f deployment-report.json
$DP jq . deployment-report.json
$DP jq .version deployment-report.json -r
$DP jq .stage deployment-report.json -r
$DP jq .hostnames[0] deployment-report.json -r
$DP jq .brancher_hypernodes[0] deployment-report.json -r

$DP hypernode-deploy cleanup -vvv
