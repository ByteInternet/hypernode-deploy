## Hypernode Deploy

This is the official application deployment tool for the Hypernode, a highly automated hosting platform for ecommerce applications.

If you want to use Hypernode Deploy in your shop you will probably want to head over to the [hypernode-deploy-configuration repository](https://github.com/ByteInternet/hypernode-deploy-configuration) instead.

If you are looking to see how the internals of the deployment tool work or even want to contribute a change to the project yourself, then you're in the right place.

This project builds the `hypernode/deploy` container image, which can be found on [quay.io/hypernode/deploy](https://quay.io/hypernode/deploy).

## Building and running a local image

If you don't want to use a pre-built image from https://quay.io/hypernode/deploy or if you are doing development on this project and you want to test out your changes, you can built an image locally and use that.

```bash
export DOCKER_TAG=hypernode_deploy_dev:latest
export PHP_VERSION=8.2
export NODE_VERSION=18
docker build -t "$DOCKER_TAG" -f "./ci/build/Dockerfile" \
    --build-arg PHP_VERSION=$PHP_VERSION \
    --build-arg NODE_VERSION=$NODE_VERSION \
    .
```

This will give you a locally built image:
```console
$ docker images | grep hypernode_deploy_dev
localhost/hypernode_deploy_dev  latest      5280aaef3a82  52 seconds ago  842 MB
```

That you could then use like:
```console
$ rm -Rf vendor
$ docker run --rm -it --env SSH_PRIVATE_KEY="$(cat ~/.ssh/yourdeploykey | base64)" -v ${PWD}:/build hypernode_deploy_dev:latest hypernode-deploy build -vvv
$ docker run --rm -it --env SSH_PRIVATE_KEY="$(cat ~/.ssh/yourdeploykey | base64)" -v ${PWD}:/build hypernode_deploy_dev:latest hypernode-deploy deploy staging -vvv
```

## Tests

### Static tests
To run the static tests, please run the following commands:

```bash
composer --working-dir tools install
tools/vendor/bin/grumphp run --config tools/grumphp.yml
```
