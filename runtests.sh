#!/usr/bin/env bash

set -e
set -x

# Handy aliases
export HN="docker-compose exec -T hypernode"
export DP="docker-compose exec -T deploy"

# Clear up env
trap "docker-compose down -v" EXIT

docker-compose up -d deploy

# Make sure MAGENTO_REPO exists and has magento2 install
MAGENTO_REPO=${MAGENTO_REPO:-../magento2.komkommer.store}
if [ ! -d $MAGENTO_REPO ]; then
    mkdir -p $MAGENTO_REPO
    $DP composer create-project --repository=https://mage-os.hypernode.com/mirror/ magento/project-community-edition /web
fi

# Start hypernode-docker
docker-compose up -d hypernode

# Build
if [ ! -e "${MAGENTO_REPO}/build" ]; then
    $DP hypernode-deploy build -f /deploy_simple.php
else
    echo "Build folder already exists, skipping build"
fi

# Prepare env
rm "${MAGENTO_REPO}/app/etc/env.php" || /bin/true
echo "Waiting for MySQL to be available on the Hypernode container"
$HN bash -c "until mysql -e 'select 1' 2> /dev/null ; do sleep 1; done"
$HN mkdir -p /data/web/apps/magento2.komkommer.store/shared/app/etc/

# Loop until elasticsearch is running in the Hypernode container
echo "Waiting for Elasticsearch to be available on the Hypernode container"
$HN bash -c "until curl -s http://localhost:9200/_cluster/health | grep -q '\"status\":\"green\"' ; do sleep 1; done"

# You need a working Magento install before you can use the hn-deploy
# This sets up the database on the Hypernode container and generates a valid env.php
$HN mysql -e "CREATE DATABASE dummytag_preinstalled_magento"
function install_magento() {
    local pw=$($HN bash -c "grep password /data/web/.my.cnf | cut -d' ' -f3")

    # Strip carriage return of pw and saves it in a new variable
    pw=$(echo $pw | tr -d '\r')

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

###################################
# TESTS HAPPEN FROM THIS POINT ON #
###################################

# SSH from deploy container to hypernode container
$DP hypernode-deploy deploy production -f /deploy_simple.php

# Check if deployment made only one release
test $($HN ls /data/web/apps/magento2.komkommer.store/releases/ | wc -l) = 1

# Deploy again
$DP hypernode-deploy deploy production -f /deploy_simple.php

# Check if another deployment was made
test $($HN ls /data/web/apps/magento2.komkommer.store/releases/ | wc -l) = 2
