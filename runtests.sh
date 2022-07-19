#!/usr/bin/env bash

set -e
set -x

# Handy aliases
export HN="docker-compose exec -T hypernode"
export DP="docker-compose run -T deploy"

# Clear up env
trap "docker-compose down -v" EXIT

# Start hypernode-docker
docker-compose up -d hypernode

# Build
if [ ! -e "${MAGENTO_REPO:-../magento2.komkommer.store}/build" ]; then
    $DP hypernode-deploy build
else
    echo "Build folder already exists, skipping build"
fi

# Prepare env
rm ../magento2.komkommer.store/app/etc/env.php || /bin/true
echo "Waiting for MySQL to be available on the Hypernode container"
$HN bash -c "until mysql -e 'select 1' 2> /dev/null ; do sleep 1; done"
$HN mkdir -p /data/web/apps/magento2.komkommer.store/shared/app/etc/

# You need a working Magento install before you can use the hn-deploy
# This sets up the database on the Hypernode container and generates a valid env.php
$HN mysql -e "CREATE DATABASE dummytag_preinstalled_magento"
function install_magento() {
    local pw=$($HN bash -c "grep password /data/web/.my.cnf | cut -d' ' -f3")
    $HN bash -c "/banaan/bin/magento setup:install  \
    --base-url=http://magento2.komkommer.store  \
    --db-host=mysqlmaster.dummytag.hypernode.io  \
    --db-name=dummytag_preinstalled_magento --db-user=app  \
    --db-password=$pw  \
    --admin-firstname=admin --admin-lastname=admin  \
    --admin-email=admin@admin.com --admin-user=admin  \
    --admin-password=admin123 --language=en_US --currency=USD  \
    --timezone=America/Chicago --elasticsearch-host=localhost"
}
install_magento || install_magento  # Second time works?
$HN cp /banaan/app/etc/env.php /data/web/apps/magento2.komkommer.store/shared/app/etc/env.php
$HN chown -R app:app /data/web/apps/magento2.komkommer.store

echo "Waiting for SSH to be available on the Hypernode container"
$DP bash -c "until ssh app@hypernode echo UP! 2> /dev/null ; do sleep 1; done"
chmod 0600 ci/test/.ssh/id_rsa

# SSH from deploy container to hypernode container
$DP hypernode-deploy deploy production -f /web/deploy_simple.php

# Check if deployment made only one release
test $($HN ls /data/web/apps/magento2.komkommer.store/releases/ | wc -l) = 1

# Deploy again
$DP hypernode-deploy deploy production -f /web/deploy_simple.php

# Check if another deployment was made
test $($HN ls /data/web/apps/magento2.komkommer.store/releases/ | wc -l) = 2
