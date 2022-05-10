#!/usr/bin/env sh
set -e

docker login -u gitlab-ci-token -p $CI_JOB_TOKEN git-registry.emico.nl

sed -i "s/PHP_VERSION/${PHP_VERSION}/g" ./ci/container-structure-test.yaml
sed -i "s/NODE_VERSION/${NODE_VERSION}/g" ./ci/container-structure-test.yaml

if [ "$CI_COMMIT_TAG" ]; then
    IMAGE="${CONTAINER_IMAGE}:${CI_COMMIT_TAG}-php${PHP_VERSION}-node${NODE_VERSION}"
else
    IMAGE="${CONTAINER_IMAGE}:php${PHP_VERSION}-node${NODE_VERSION}"
fi

docker pull ${IMAGE}
container-structure-test test --image ${IMAGE} --config ./ci/container-structure-test.yaml
