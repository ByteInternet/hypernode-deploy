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

## Architecture

Flow of `hypernode-deploy build`:

```mermaid
graph TD

build --> deploy:info --> prepare:ssh --> build:compile:start;
build:compile:stop --> build:package;

subgraph build:compile
    build:compile:start --> build:compile:prepare --> tasks --> build:compile:stop;
end
```

Flow of `hypernode-deploy deploy`:

```mermaid
graph TD

deploy --> deploy:upload:start;
deploy:upload:stop --> deploy:link:start;
deploy:link:stop --> deploy:finalize:start;

subgraph deploy:upload
    deploy:upload:start --> deploy:info --> prepare:ssh --> deploy:prepare_release:start;
    deploy:prepare_release:stop --> deploy:copy:start;
    deploy:copy:stop --> deploy:deploy --> tasks --> deploy:upload:stop;

    subgraph deploy:prepare_release
        deploy:prepare_release:start --> deploy:prepare --> deploy:release --> deploy:prepare_release:stop;
    end
    
    subgraph deploy:copy
        deploy:copy:start --> deploy:copy:code --> deploy:shared --> deploy:copy:stop;
    end
end

subgraph deploy:link
    deploy:link:start --> deploy:symlink --> deploy:public_link --> deploy:link:stop;
end

subgraph deploy:finalize
    deploy:finalize:start --> deploy:after --> cleanup --> success --> deploy:finalize:stop;
end

deploy:symlink --> newrelic:notify;
deploy:symlink --> cachetool:clear:opcache;
deploy:symlink --> deploy:cloudflare;
cachetool:clear:opcache --> cachetool:cleanup;
success --> slack:notify:success;
```
