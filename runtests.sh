#!/usr/bin/env bash

set -e
set -x

# Clear up env
trap "docker-compose down -v" EXIT

# Start hypernode-docker
docker-compose up -d hypernode

# Build
if [ ! -e "../magento2.komkommer.store/vendor" ]; then
    docker-compose run deploy hypernode-deploy build
else
    echo "Vendor folder already exists, skipping build"
fi

# Prepare Hypernode env
docker-compose exec hypernode mkdir -p /data/web/apps/magento2.komkommer.store
docker-compose exec hypernode chown -R app:app /data/web/apps/magento2.komkommer.store

echo "Waiting for SSH to be available on the Hypernode container"
docker-compose run deploy bash -c "until ssh app@hypernode hostname 2> /dev/null ; do sleep 1; done"

# SSH from deploy container to hypernode container
chmod 0600 ci/test/.ssh/id_rsa
docker-compose run deploy cat /web/deploy.php
docker-compose run deploy hypernode-deploy deploy production -f /web/deploy_simple.php
