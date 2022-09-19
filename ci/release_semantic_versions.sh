#!/usr/bin/env bash

IMAGE=${IMAGE:-quay.io/hypernode/deploy}
INPUT_VERSION=${INPUT_VERSION:-}
TAG_SPECS="php${PHP_VERSION}-node${NODE_VERSION}"

if [ -z "${INPUT_VERSION}" ]; then
    echo "No input version provided, stopping".
    exit 1
fi

if echo "${INPUT_VERSION}" | grep -E -q "(dev|rc|alpha|beta)"; then
    echo "Not publishing short semantic versions for dev, rc, alpha or beta tag"
    exit 0
fi

function tag_and_publish () {
    SOURCE_TAG=$1
    TARGET_TAG=$2
    docker tag "${SOURCE_TAG}" "${TARGET_TAG}"
    docker push "${TARGET_TAG}"
}

LOCAL_IMAGE_TAG="$IMAGE:$INPUT_VERSION-$TAG_SPECS"
if echo "${INPUT_VERSION}" | grep -F "."; then
    MAJOR_VERSION=$(echo "${INPUT_VERSION}" | cut -d. -f1)
    MINOR_VERSION=$(echo "${INPUT_VERSION}" | cut -d. -f2)
    PATCH_VERSION=$(echo "${INPUT_VERSION}" | cut -d. -f3)

    if [ -n "${MINOR_VERSION}" ] && [ -n "${PATCH_VERSION}" ]; then
        if echo "${PATCH_VERSION}" | grep -q "-"; then
            #PATCH_SUFFIX=$(echo "${PATCH_VERSION}" | cut -d- -f2-)
            PATCH_VERSION=$(echo "${PATCH_VERSION}" | cut -d- -f1)
        fi
        tag_and_publish "$LOCAL_IMAGE_TAG" "$IMAGE:$MAJOR_VERSION.$MINOR_VERSION.$PATCH_VERSION-$TAG_SPECS"
    fi

    if [ -n "$MINOR_VERSION" ]; then
        tag_and_publish "$LOCAL_IMAGE_TAG" "$IMAGE:$MAJOR_VERSION.$MINOR_VERSION-$TAG_SPECS"
    fi

    tag_and_publish "$LOCAL_IMAGE_TAG" "$IMAGE:$MAJOR_VERSION-$TAG_SPECS"
fi

if [[ "${PHP_VERSION}" == "${LATEST_PHP_VERSION}" ]] && [[ "${NODE_VERSION}" == "${LATEST_NODE_VERSION}" ]]; then
    tag_and_publish "$LOCAL_IMAGE_TAG" "$IMAGE:latest"
fi
