#!/usr/bin/env sh
set -e

cd "$(dirname "$0")/../"

# Install build tool
cd build
composer install --no-dev --optimize-autoloader
cd ../

# Run build
./build/vendor/bin/box compile
