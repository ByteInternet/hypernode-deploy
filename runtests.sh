#!/usr/bin/env bash

set -e
set -x

# Clear up env
docker-compose kill
docker-compose rm -f

# Start hypernode-docker
docker-compose up -d hypernode

# Wait for healthcheck
sleep 5

# Build
docker-compose run deploy hypernode-deploy build

# Find hostname of hypernode container in /etc/hosts
HOSTNAME=$(docker-compose exec hypernode cat /etc/hosts | grep hypernode | awk '{print $2}')

# SSH from deploy container to hypernode container
chmod 0600 ci/test/.ssh/id_rsa
docker-compose run deploy ssh -i /root/.ssh/id_rsa hypernode hostname

# Clear up env
docker-compose kill
docker-compose rm -f
