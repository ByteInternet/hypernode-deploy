#!/usr/bin/env bash

set -e
set -x

# Handy aliases
HN="docker-compose exec -T hypernode"
DP="docker-compose exec -T deploy"
HNDP="/usr/local/bin/hypernode-deploy"

chmod 0600 ci/test/.ssh/id_rsa
chmod 0600 ci/test/.ssh/authorized_keys
docker-compose up -d

sudo apt update && sudo apt-get install -y \
  apt-transport-https bash-completion ca-certificates curl git gpg gnupg htop locales lsb-release ripgrep rsync vim vim-nox wget zip

sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
  echo "deb https://packages.sury.org/php/ buster main" | sudo tee /etc/apt/sources.list.d/php.list

sudo apt update && sudo apt-get install -y build-essential cmake php8.1-bcmath php8.1-cli php8.1-curl php8.1-gd php8.1-intl php8.1-mbstring php8.1-mysql php8.1-soap php8.1-zip php8.1-xml dnsutils net-tools
wget https://raw.githubusercontent.com/composer/getcomposer.org/master/web/installer -O - -q | php -- --quiet && \
  sudo mv composer.phar /usr/local/bin/composer

composer install --no-dev --optimize-autoloader
make compile
# IP=$(docker ps |& grep docker.hypernode.com | awk '{print$1}' | xargs docker inspect | jq -r ".[].NetworkSettings.Networks[].IPAddress")
ssh -p 2222 -o StrictHostKeyChecking=no root@localhost -i ci/test/.ssh/id_rsa hostname

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

# Clear up env
trap "docker-compose down -v" EXIT

docker-compose up -d

# Create working initial Magento install
$HN composer create-project --repository=https://mage-os.hypernode.com/mirror/ magento/project-community-edition /data/web/magento2
echo "Waiting for MySQL to be available on the Hypernode container"
$HN bash -c "until mysql -e 'select 1' ; do sleep 1; done"
install_magento

# Copy env to the deploy container
$HN /data/web/magento2/bin/magento app:config:dump scopes themes
echo "Waiting for SSH to be available on the Hypernode container"
$HN cat /root/.ssh/authorized_keys
$DP ssh-keygen -y -f /root/.ssh/id_rsa
$DP stat /root/.ssh/id_rsa
$HN cat /etc/ssh/sshd_config
# Allocate pseudotty because github actions doesn't support TTY
# $DP bash -c "until ssh -tt -vvv -i /root/.ssh/id_rsa root@hypernode echo UP! ; do sleep 1; done"
$DP bash -c 'eval $(ssh-agent) && ssh-add /root/.ssh/id_rsa && rsync -v -e "ssh -vvv" -a hypernode:/data/web/magento2/ /web'
$DP rm /web/app/etc/env.php

# Build
$DP hypernode-deploy build -f /deploy_simple.php

# Prepare env
$HN mkdir -p /data/web/apps/magento2.komkommer.store/shared/app/etc/
$HN cp /data/web/magento2/app/etc/env.php /data/web/apps/magento2.komkommer.store/shared/app/etc/env.php
$HN chown -R app:app /data/web/apps/magento2.komkommer.store

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
