## Hypernode Deploy

This is the official application deployment tool for the Hypernode, a highly automated hosting platform for ecommerce applications.

If you want to use Hypernode Deploy in your shop you will probably want to head over to the [hypernode-deploy-configuration repository](https://github.com/ByteInternet/hypernode-deploy-configuration) instead.

If you are looking to see how the internals of the deployment tool work or even want to contribute a change to the project yourself, then you're in the right place.

This project builds the hypernode/deploy container image.

## Switching node version

```bash
source /etc/profile.d/nvm.sh
nvm install -s stable
```

## Tests

### Static tests
To run the static tests, please run the following commands:

```bash
composer --working-dir tools install
tools/vendor/bin/grumphp run --config tools/grumphp.yml
```

### Docker container
We use Google Container Structure Tests over https://github.com/aelsabbahy/goss because the Hipex deploy container does not require a health check.

## Build images locally

```bash
CONTAINER_IMAGE=hipex/deploy/dev \
CI_COMMIT_TAG=2.0.2 \
PHP_VERSION=7.4 \
NODE_VERSION=14 \
LOCAL_BUILD= \
./ci/build.sh
```

## Run with local image 

```bash
rm -Rf vendor
docker run --rm -it --env SSH_PRIVATE_KEY="$(cat ~/.ssh/id_rsa_mydeploykey | base64)" -v ${PWD}:/build hipex/deploy/dev:2.1.0-php7.3-node13 hipex-deploy build -vvv
```
