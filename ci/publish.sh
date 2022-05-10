#!/usr/bin/env sh
set -e

DEFAULT_PHP_VERSION="7.1"
DEFAULT_NODE_VERSION="8"

function deploy {
    echo "This is output from build.sh"

    docker pull ${CONTAINER_IMAGE}:${1}
    docker tag ${CONTAINER_IMAGE}:${1} ${CONTAINER_IMAGE_HUB}:${1}
    docker push ${CONTAINER_IMAGE_HUB}:${1}
}

docker login -u gitlab-ci-token -p $CI_JOB_TOKEN git-registry.emico.nl
docker login -u ${DOCKER_HUB_USERNAME} -p ${DOCKER_HUB_PASSWORD}

if [ -z "$PHP_VERSION" ]; then
    PHP_VERSION="$DEFAULT_PHP_VERSION" # PHP_VERSION is empty set default value
fi

if [ -z "$NODE_VERSION" ]; then
    NODE_VERSION="$DEFAULT_NODE_VERSION" # NODE_VERSION is empty set default value
fi

IMAGE_NAME="php${PHP_VERSION}-node${NODE_VERSION}"
if [ "$CI_COMMIT_TAG" ]; then
    deploy "${CI_COMMIT_TAG}-${IMAGE_NAME}" "${IMAGE_NAME}"
else
    deploy "${IMAGE_NAME}" "${IMAGE_NAME}"

    # If PHP_VERSION and NODE_VERSION is default (latest) deploy without tag
    if [ "$PHP_VERSION" == "$DEFAULT_PHP_VERSION" ] && [ "$NODE_VERSION" == "$DEFAULT_NODE_VERSION" ]; then
        deploy
    fi
fi
