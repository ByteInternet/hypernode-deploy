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
rm -rf /tmp/m2build/var/log

# Build application
$DP hypernode-deploy build -f /web/deploy.php -vvv

##########################################
# DEPLOY WITHOUT PLATFORM CONFIGURATIONS #
# This should pass, but not generate any #
# Nginx/Supervisor/etc configs           #
##########################################
# SSH from deploy container to Hypernode container
$DP hypernode-deploy deploy staging -f /web/deploy.php -vvv

# Run some tests, the staging environment should not have a brancher node
$DP ls -l
$DP test -f deployment-report.json
$DP jq . deployment-report.json
$DP jq .version deployment-report.json -r -e
$DP jq .stage deployment-report.json -r -e
$DP jq .hostnames[0] deployment-report.json -r -e
$DP jq '.brancher_hypernodes | select(length == 0)' deployment-report.json -r -e

# Now do a test deploy which should have a brancher node.
$DP hypernode-deploy deploy test -f /web/deploy.php -vvv

$DP ls -l
$DP test -f deployment-report.json
$DP jq . deployment-report.json
$DP jq .version deployment-report.json -r -e
$DP jq .stage deployment-report.json -r -e
$DP jq .hostnames[0] deployment-report.json -r -e
$DP jq .brancher_hypernodes[0] deployment-report.json -r -e

# cleanup data
$DP hypernode-deploy cleanup -vvv

rm -f deployment-report.json

# Now do a test deploy again to deploy to a brancher node and clean it up by hnapi and labels matching
$DP hypernode-deploy deploy test -f /web/deploy.php -vvv

$DP ls -l
$DP test -f deployment-report.json
$DP jq . deployment-report.json
$DP jq .version deployment-report.json -r -e
$DP jq .stage deployment-report.json -r -e
$DP jq .hostnames[0] deployment-report.json -r -e
$DP jq .brancher_hypernodes[0] deployment-report.json -r -e

# Remove deployment report to make sure we can clean up using hnapi and labels matching
$DP rm -f deployment-report.json

# cleanup data
$DP hypernode-deploy cleanup test -vvv
