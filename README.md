## Hypernode Deploy

This is the official application deployment tool for the Hypernode, a highly automated hosting platform for ecommerce applications.

If you want to use Hypernode Deploy in your shop you will probably want to head over to the [hypernode-deploy-configuration repository](https://github.com/ByteInternet/hypernode-deploy-configuration) instead.

If you are looking to see how the internals of the deployment tool work or even want to contribute a change to the project yourself, then you're in the right place.

This project builds the hypernode/deploy container image.

## Building and running a local image

If you don't want to use a pre-built image from https://quay.io/hypernode/deploy or if you are doing development on this project and you want to test out your changes, you can built an image locally and use that.

```bash
export LOCAL_BUILD=  # So we don't try to push
export CONTAINER_IMAGE=hypernode/deploy/dev
export PHP_VERSION=7.4
export NODE_VERSION=14
bash -x ci/build.sh
```

This will give you a locally built image:
```bash
$ docker images | grep hypernode/deploy/dev
hypernode/deploy/dev                                        php7.4-node14         ece785ad21f5   2 minutes ago   753MB
```

That you could then use like:
```bash
$ rm -Rf vendor  # in case we need to do some cleanup
$ docker run --rm -it --env SSH_PRIVATE_KEY="$(cat ~/.ssh/yourdeploykey | base64)" -v ${PWD}:/build hypernode/deploy/dev:php7.4-node14 hypernode-deploy build -vvv
$ docker run --rm -it --env SSH_PRIVATE_KEY="$(cat ~/.ssh/yourdeploykey | base64)" -v ${PWD}:/build hypernode/deploy/dev:php7.4-node14 hypernode-deploy deploy staging -vvv
```

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
