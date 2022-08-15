#!/usr/bin/env bash

set -e
set -x

export PHP_VERSION_SHORT=$(echo "${PHP_VERSION:-8.1}" | sed 's/\.//')

# Handy aliases
HN="docker-compose exec -T hypernode"
DP="docker-compose exec -T deploy"
DP1="docker-compose exec --workdir=/web1 -T deploy"
DP2="docker-compose exec --workdir=/web2 -T deploy"

function install_magento() {
    $HN mysql -e "DROP DATABASE IF EXISTS dummytag_preinstalled_magento"
    $HN mysql -e "CREATE DATABASE dummytag_preinstalled_magento"
    local pw=$($HN bash -c "grep password /data/web/.my.cnf | cut -d' ' -f3")

    # Strip carriage return of pw and saves it in a new variable
    pw=$(echo $pw | tr -d '\r')

    $HN bash -c "/data/web/magento2/bin/magento setup:install  \
    --base-url=http://banaan1.store  \
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

# Create second app
$DP cp -ra /web /web1
$DP cp -ra /web /web2

# Build both apps
$DP1 hypernode-deploy build -v -f /web1/deploy1.php
$DP2 hypernode-deploy build -v -f /web2/deploy2.php

# Prepare env
$HN mkdir -p /data/web/apps/banaan1.store/shared/app/etc/
$HN cp /data/web/magento2/app/etc/env.php /data/web/apps/banaan1.store/shared/app/etc/env.php
$HN mkdir -p /data/web/apps/banaan2.store/shared/app/etc/
$HN cp /data/web/magento2/app/etc/env.php /data/web/apps/banaan2.store/shared/app/etc/env.php
$HN chown -R app:app /data/web/apps

##########################################
# DEPLOY WITHOUT PLATFORM CONFIGURATIONS #
# This should pass, but not generate any #
# Nginx/Supervisor/etc configs           #
##########################################
# SSH from deploy container to hypernode container
$DP1 hypernode-deploy deploy production -f /web1/deploy1_without_platformconfig.php -v

# Check if deployment made only one release for store1
test $($HN ls /data/web/apps/banaan1.store/releases/ | wc -l) = 1

# Platform configs shouldn't be present yet
$HN test ! -d /data/web/nginx/banaan1.store
$HN test ! -d /data/web/supervisor/banaan1.store
$HN crontab -l -u app | grep "### BEGIN banaan1.store ###" && exit 1

##################
# DEPLOY STORE 2 #
##################
# Store 2
$DP2 hypernode-deploy deploy production -f /web2/deploy2.php -v

# Check if deployment made only one release for store2
test $($HN ls /data/web/apps/banaan2.store/releases/ | wc -l) = 1
$HN ls -al /data/web/nginx/banaan2.store/
$HN ls -al /data/web/apps/banaan2.store/current/
$HN ls -al /data/web/apps/banaan2.store/current/nginx/
$HN test -f /data/web/nginx/banaan2.store/server.example.conf || ($HN ls -al /data/web/nginx && $HN ls -al /data/web/nginx/banaan2.store && exit 1)
$HN test $($HN readlink -f /data/web/nginx/banaan2.store) = /data/web/apps/banaan2.store/releases/1/nginx

##################################
# DEPLOY PLATFORM CONFIGURATIONS #
# Now we should get revisions of #
# all platform configs.          #
##################################
$DP1 hypernode-deploy deploy production -v -f /web1/deploy1.php

# Check if example location block was placed
$HN ls -al /data/web/nginx/banaan1.store/
$HN ls -al /data/web/apps/banaan1.store/current/
$HN ls -al /data/web/apps/banaan1.store/current/nginx/
$HN test -f /data/web/nginx/banaan1.store/server.example.conf || ($HN ls -al /data/web/nginx && $HN ls -al /data/web/nginx/banaan1.store && exit 1)
$HN test $($HN readlink -f /data/web/nginx/banaan1.store) = /data/web/apps/banaan1.store/releases/2/nginx

$HN test -f /data/web/supervisor/banaan1.store/example.conf || ($HN ls -al /data/web/supervisor/ && exit 1)
$HN test $($HN readlink -f /data/web/supervisor/banaan1.store) = /data/web/apps/banaan1.store/releases/2/supervisor

# Test this once we enable supervisor in the hypernode docker image
# $HN supervisorctl status | grep example | grep -v FATAL || ($HN supervisorctl status && exit 1)

# Test if varnish vcl has been placed
$HN test $($HN readlink -f /data/web/varnish/banaan1.store/varnish.vcl) = /data/web/apps/banaan1.store/releases/2/varnish/varnish.vcl

# Check the content of the crontab block
$HN crontab -l -u app | grep "### BEGIN banaan1.store ###"
$HN crontab -l -u app | grep "### END banaan1.store ###"
$HN crontab -l -u app | sed -n -e '/### BEGIN banaan1.store ###/,/### END banaan1.store ###/ p' | grep "banaan"

######################################
# REMOVE A NGINX LOCATION            #
# Create a new release but make sure #
# that the file is removed in the    #
# new release.                       #
######################################
# Remove example location
$DP rm /web1/etc/nginx/server.example.conf

# Deploy again
$DP1 hypernode-deploy deploy production -f /web1/deploy1.php

# Check if another deployment was made
test $($HN ls /data/web/apps/banaan1.store/releases/ | wc -l) = 3
$HN test $($HN readlink -f /data/web/nginx/banaan1.store) = /data/web/apps/banaan1.store/releases/3/nginx
$HN test $($HN readlink -f /data/web/supervisor/banaan1.store) = /data/web/apps/banaan1.store/releases/3/supervisor
$HN test $($HN readlink -f /data/web/varnish/banaan1.store/varnish.vcl) = /data/web/apps/banaan1.store/releases/3/varnish/varnish.vcl

# Verify example location block is removed
$HN test ! -f /data/web/nginx/banaan1.store/server.example.conf || ($HN ls -al /data/web/nginx/banaan1.store && exit 1)

# Check if the second application is still working as intended
test $($HN ls /data/web/apps/banaan2.store/releases/ | wc -l) = 1
$HN ls -al /data/web/nginx/banaan2.store/
$HN ls -al /data/web/apps/banaan2.store/current/
$HN ls -al /data/web/apps/banaan2.store/current/nginx/
$HN test -f /data/web/nginx/banaan2.store/server.example.conf || ($HN ls -al /data/web/nginx && $HN ls -al /data/web/nginx/banaan2.store && exit 1)
$HN test $($HN readlink -f /data/web/nginx/banaan2.store) = /data/web/apps/banaan2.store/releases/1/nginx
