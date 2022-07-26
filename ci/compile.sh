#!/usr/bin/env bash
set -e

cd "$(dirname "$0")/../"

# Install build tool

# Run build
if [[ "${PHP_VERSION}" == 7.* ]]; then
    wget -q https://github.com/box-project/box/releases/download/3.9.1/box.phar
else
    wget -q https://github.com/box-project/box/releases/download/3.16.0/box.phar
fi

mv box.phar /usr/local/bin/box
chmod +x /usr/local/bin/box

box compile
