#!/usr/bin/env bash

set -e
set -x

export PHP_VERSION_SHORT=$(echo "${PHP_VERSION:-8.1}" | sed 's/\.//')

# Handy aliases
HN="docker-compose exec -T hypernode"
DP="docker-compose exec -T deploy"

function install_magento() {
    $HN mysql -e "DROP DATABASE IF EXISTS dummytag_preinstalled_magento"
    $HN mysql -e "CREATE DATABASE dummytag_preinstalled_magento"
    local pw=$($HN bash -c "grep password /data/web/.my.cnf | cut -d' ' -f3")

    # Strip carriage return of pw and saves it in a new variable
    pw=$(echo $pw | tr -d '\r')

    $HN bash -c "/data/web/magento2/bin/magento setup:install  \
    --base-url=http://magento2.komkommer.store  \
    --db-host=mysqlmaster.dummytag.hypernode.io  \
    --db-name=dummytag_preinstalled_magento --db-user=app  \
    --db-password=$pw  \
    --admin-firstname=admin --admin-lastname=admin  \
    --admin-email=admin@admin.com --admin-user=admin  \
    --admin-password=admin123 --language=en_US --currency=USD  \
    --timezone=America/Chicago --elasticsearch-host=localhost"
}

# Install docker-compose if it's not installed
if ! [ -x "$(command -v docker-compose)" ]; then
    pip install docker-compose
fi

# Clear up env
trap "docker-compose down -v" EXIT

docker-compose up -d

# Create working initial Magento install on the Hypernode container
$HN composer create-project --repository=https://mage-os.hypernode.com/mirror/ magento/project-community-edition /data/web/magento2
echo "Waiting for MySQL to be available on the Hypernode container"
$HN bash -c "until mysql -e 'select 1' ; do sleep 1; done"
install_magento

# Copy env to the deploy container
$HN /data/web/magento2/bin/magento app:config:dump scopes themes
echo "Waiting for SSH to be available on the Hypernode container"
chmod 0600 ci/test/.ssh/id_rsa
chmod 0600 ci/test/.ssh/authorized_keys
$DP rsync -a app@hypernode:/data/web/magento2/ /web
$DP rsync -v -a /config/ /web
$DP rm /web/app/etc/env.php

# Build
$DP hypernode-deploy build -v

# Prepare env
$HN mkdir -p /data/web/apps/magento2.komkommer.store/shared/app/etc/
$HN cp /data/web/magento2/app/etc/env.php /data/web/apps/magento2.komkommer.store/shared/app/etc/env.php
$HN chown -R app:app /data/web/apps/magento2.komkommer.store

##########################################
# DEPLOY WITHOUT PLATFORM CONFIGURATIONS #
# This should pass, but not generate any #
# Nginx/Supervisor/etc configs           #
##########################################
# SSH from deploy container to hypernode container
$DP hypernode-deploy deploy production -f /web/deploy_without_platformconfig.php -v

# Check if deployment made only one release
test $($HN ls /data/web/apps/magento2.komkommer.store/releases/ | wc -l) = 1

# Platform configs shouldn't be present yet
$HN test ! -d /data/web/nginx/magento2.komkommer.store
$HN test ! -d /data/web/supervisor/magento2.komkommer.store
$HN crontab -l -u app | grep "### BEGIN magento2.komkommer.store ###" && exit 1

##################################
# DEPLOY PLATFORM CONFIGURATIONS #
# Now we should get revisions of #
# all platform configs.          #
##################################
$DP hypernode-deploy deploy production -v

# Check if example location block was placed
$HN ls -al /data/web/nginx/magento2.komkommer.store/
$HN ls -al /data/web/apps/magento2.komkommer.store/current/
$HN ls -al /data/web/apps/magento2.komkommer.store/current/nginx/
$HN test -f /data/web/nginx/magento2.komkommer.store/server.example.conf || ($HN ls -al /data/web/nginx && $HN ls -al /data/web/nginx/magento2.komkommer.store && exit 1)
$HN test $($HN readlink -f /data/web/nginx/magento2.komkommer.store) = /data/web/apps/magento2.komkommer.store/releases/2/nginx

$HN test -f /data/web/supervisor/magento2.komkommer.store/example.conf || ($HN ls -al /data/web/supervisor/ && exit 1)
$HN test $($HN readlink -f /data/web/supervisor/magento2.komkommer.store) = /data/web/apps/magento2.komkommer.store/releases/2/supervisor

# Test this once we enable supervisor in the hypernode docker image
# $HN supervisorctl status | grep example | grep -v FATAL || ($HN supervisorctl status && exit 1)

# Test if varnish vcl has been placed
$HN test $($HN readlink -f /data/web/varnish/magento2.komkommer.store/varnish.vcl) = /data/web/apps/magento2.komkommer.store/releases/2/varnish/varnish.vcl

# Check the content of the crontab block
$HN crontab -l -u app | grep "### BEGIN magento2.komkommer.store ###"
$HN crontab -l -u app | grep "### END magento2.komkommer.store ###"
$HN crontab -l -u app | sed -n -e '/### BEGIN magento2.komkommer.store ###/,/### END magento2.komkommer.store ###/ p' | grep "banaan"

######################################
# REMOVE A NGINX LOCATION            #
# Create a new release but make sure #
# that the file is removed in the    #
# new release.                       #
######################################
# Remove example location
$DP rm /web/etc/nginx/server.example.conf

# Deploy again
$DP hypernode-deploy deploy production

# Check if another deployment was made
test $($HN ls /data/web/apps/magento2.komkommer.store/releases/ | wc -l) = 3
$HN test $($HN readlink -f /data/web/nginx/magento2.komkommer.store) = /data/web/apps/magento2.komkommer.store/releases/3/nginx
$HN test $($HN readlink -f /data/web/supervisor/magento2.komkommer.store) = /data/web/apps/magento2.komkommer.store/releases/3/supervisor
$HN test $($HN readlink -f /data/web/varnish/magento2.komkommer.store/varnish.vcl) = /data/web/apps/magento2.komkommer.store/releases/3/varnish/varnish.vcl

# Verify example location block is removed
$HN test ! -f /data/web/nginx/magento2.komkommer.store/server.example.conf || ($HN ls -al /data/web/nginx/magento2.komkommer.store && exit 1)
