#!/usr/bin/env sh
set -e

DEFAULT_PHP_VERSION="7.2"
DEFAULT_NODE_VERSION="12"

echo "${CONTAINER_IMAGE}"

cd "$(dirname "$0")/../"

function build {
    docker pull ${CONTAINER_IMAGE}:${2} || true
    docker build -t ${CONTAINER_IMAGE}:${1} --build-arg PHP_VERSION=${PHP_VERSION} --build-arg NODE_VERSION=${NODE_VERSION} -f ./ci/build/Dockerfile .

    # Push image unless LOCAL_BUILD var is set
    if [ -z "${LOCAL_BUILD+xxx}" ]; then
        docker push ${CONTAINER_IMAGE}:${1}
    fi
}

if [ "$CI_JOB_TOKEN" ]; then
  docker login -u gitlab-ci-token -p $CI_JOB_TOKEN git-registry.emico.nl
fi

if [ -z "$PHP_VERSION" ]; then
    PHP_VERSION="$DEFAULT_PHP_VERSION" # PHP_VERSION is empty set default value
fi

if [ -z "$NODE_VERSION" ]; then
    NODE_VERSION="$DEFAULT_NODE_VERSION" # NODE_VERSION is empty set default value
fi

IMAGE_NAME="php${PHP_VERSION}-node${NODE_VERSION}"
if [ "$CI_COMMIT_TAG" ]; then
    build "${CI_COMMIT_TAG}-${IMAGE_NAME}" "${IMAGE_NAME}"
else
    build "${IMAGE_NAME}" "${IMAGE_NAME}"
fi
