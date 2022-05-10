DISTROBOX_NAME := $$(basename $$(pwd))
CONTAINER_MANAGER ?= docker

.PHONY: build

build: create-distrobox build-with-distrobox

build-with-distrobox:
	distrobox enter $(DISTROBOX_NAME) \
	-e 'make compile'
	#distrobox stop -Y $(DISTROBOX_NAME)

clean:
	docker kill distrobox-$(DISTROBOX_NAME) || /bin/true
	docker rm -f distrobox-$(DISTROBOX_NAME)
	rm -rf .venv

compile:
	ci/compile.sh
	sudo mv build/hipex-deploy.phar /usr/local/bin/hipex-deploy

create-distrobox:
	$(CONTAINER_MANAGER) build -t distrobox-$(DISTROBOX_NAME) .distrobox
	distrobox create --image distrobox-$(DISTROBOX_NAME) $(DISTROBOX_NAME)
