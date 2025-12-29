#@author Fred Brooker <git@gscloud.cz>
MAKEFLAGS += --no-print-directory
WWW_USER := www-data

include .env
# sanitize .env variables (remove quotes and comments)
NAME := $(shell echo $(NAME))
PORT := $(shell echo $(PORT))
TAG := $(shell echo $(TAG))
VERSION := $(shell ./cli.sh app 'echo explode(" ",ENGINE)[1]')

# app checks
has_chrome := $(shell command -v google-chrome 2>/dev/null)
has_docker := $(shell command -v docker 2>/dev/null)
has_phpstan := $(shell command -v vendor/bin/phpstan 2>/dev/null)
has_rename := $(shell command -v rename 2>/dev/null)
has_wget := $(shell command -v wget 2>/dev/null)

BASE := app/base.csv
DOWNLOADS := $(HOME)/Downloads
ADMIN_FILE := $(shell mktemp)
DEFAULT_FILE := $(shell mktemp)
ADMIN_URL := 'https://docs.google.com/spreadsheets/d/e/2PACX-1vRDwThuqEPGHzRWCJNs3KRy1OO8gh_t0qMRH2e5N2Ok_dSf29tqxnAImE4pnc8B4qE_2ZJKgHIiyIIk/pub?output=csv'
DEFAULT_URL := 'https://docs.google.com/spreadsheets/d/e/2PACX-1vRrx4arHlU3KLpy3Vlw_sX9iEZz2t_gZz5SV4NFa8ufcFqbVo1Cxgsp4J81-Z02cPNPJ9Jc7b_Qy_ay/pub?output=csv'

status := $(shell docker inspect --format '{{.State.Running}}' ${NAME} 2>/dev/null)
ifeq ($(status),true)
	dot := 🟢
else
	dot := 🔴
endif

# color definitions
ifeq ($(NO_COLOR),1)
	B :=
	L :=
	R :=
	GREEN :=
	RED :=
	YELLOW :=
	BLUE :=
else
	B := $(shell tput bold)
	L := $(shell tput dim)
	R := $(shell tput sgr0)
	GREEN := $(shell tput setaf 2)
	RED := $(shell tput setaf 1)
	YELLOW := $(shell tput setaf 3)
	BLUE := $(shell tput setaf 4)
endif

all: info
info:
	@echo "${GREEN}${B}👾 ${APP_NAME}${R}"
ifneq ($(origin NAME), undefined)
ifneq ($(origin PORT), undefined)
	@echo "${R}📦️ ${B}${YELLOW}${NAME}${R}\t$(dot) \e[0;4m${NAME}${R} \t🚀 http://localhost:${PORT}\n"
endif
endif
	@echo "${L}🔧 DEVELOPMENT${R}"
	@echo "${B}install${R}\t do core installation"
	@echo "${B}doctor${R}\t check installation"
	@echo "${B}update${R}\t update dependencies"
	@echo "${B}clear${R}\t clear all temporary files"
	@echo "${B}base${R}\t download and build base Sheets CSV"
	@echo "${B}refresh${R}\t refresh Sheets CSV"
	@echo "${B}icons${R}\t rebuild icons"
	@echo "${B}sync${R}\t sync files to the remote host"
	@echo "${B}docs${R}\t fix Sheets export for CHANGELOG.md "
	@echo ""
	@echo "${L}🤯 TESTING${R}"
	@echo "${B}unit${R}\t UNIT tests"
	@echo "${B}stan${R}\t PHPStan tests"
	@echo "${B}test${R}\t LOCAL integration tests"
	@echo "${B}prod${R}\t PRODUCTION integration tests"
	@echo ""
	@echo "${L}🐳 DOCKER${R}"
	@echo "${B}build${R}\t build image"
	@echo "${B}push${R}\t push image to the Docker Hub"
	@echo "${B}exec${R}\t run a Bash terminal inside the container"
	@echo "${B}start${R}\t start container"
	@echo "${B}run${R}\t start container in the Chrome browser"
	@echo "${B}stop${R}\t stop container"
	@echo "${B}kill${R}\t kill container"
	@echo "${B}remove${R}\t remove container (forced)"
	@echo "${B}log${R}\t show container logs"

base:
ifneq ($(strip $(has_wget)),)
	@echo "download: [default]"
	@wget -q -O $(DEFAULT_FILE) $(DEFAULT_URL) || \
		(echo "Failed to download file. Exiting..." && exit 1)
	@echo "download: [admin]"
	@wget -q -O $(ADMIN_FILE) $(ADMIN_URL) || \
		(echo "Failed to download file. Exiting..." && exit 1)
	@cat $(DEFAULT_FILE) > $(BASE)
	@echo >> $(BASE)
	@tail -n +3 $(ADMIN_FILE) >> $(BASE)
	@rm -f $(DEFAULT_FILE) $(ADMIN_FILE)
	@cat $(BASE) | wc -l
	@./cli.sh clearcache
else
	$(error "ERROR: Missing wget command!")
endif

docs:
	@sed -i -e 's/`~~\*\*/`**/g' -e 's/\* \*\*~~/* ~~**/g' -e 's/ `\*\*/`**/g' CHANGELOG.md
	@echo 'Done.'

update: clear
	@./bin/update.sh

unit:
	@./cli.sh unit

clear:
	@./cli.sh clearcache
	@./cli.sh clearci
	@./cli.sh clearlogs
	@./cli.sh cleartemp

install:
	@./bin/install.sh

doctor:
	@./cli.sh doctor

sync:
	@./bin/sync.sh x
	@./bin/sync.sh b
	@./bin/sync.sh a

local: test
test:
	@./cli.sh unit
	@./cli.sh local

refresh:
	@sudo -u $(WWW_USER) -- ./cli.sh refresh
	@./cli.sh clearcache

prod:
	@./cli.sh unit
	@./cli.sh prod

icons:
	@cd ./www/img && ./create_favicons.sh

stan:
ifneq ($(strip $(has_phpstan)),)
	@vendor/bin/phpstan analyse -c phpstan.neon
else
	$(error "PHPStan is not installed")
endif
ifneq ($(strip $(PHPSTAN_EXTRA)),)
	@./phpstan_extra.sh
endif

build:
ifneq ($(TAG),)
	@echo "Building image for version: ${YELLOW}${VERSION}${R}"
	@docker build --pull -t $(TAG):latest .
ifneq ($(VERSION),)
	@docker build --pull -t $(TAG):$(VERSION) .
endif
else
	@echo "❌ missing TAG definition"
endif

push:
	@echo Version: $(VERSION)
ifneq ($(TAG),)
	@docker push $(TAG):latest
ifneq ($(VERSION),)
	@docker push $(TAG):$(VERSION)
endif
else
	@echo "❌ missing TAG definition"
endif

start:
	@bash ./bin/docker_start.sh

run:
ifneq ($(origin TAG), undefined)
	@echo "🚀 Starting container ${YELLOW}$(NAME)${R} on port ${B}$(PORT)${R}..."
	@docker run -d --rm \
		--name $(NAME) \
		-p $(PORT):80 \
		-v $(CURDIR)/app/config_private.neon:/var/www/app/config_private.neon \
		$(TAG)
	@if command -v google-chrome >/dev/null 2>&1; then \
		echo "🌐 Opening Chrome at http://localhost:$(PORT)"; \
		google-chrome http://localhost:$(PORT) >/dev/null 2>&1 & \
	else \
		echo "ℹ️  Container is up at http://localhost:$(PORT) (Chrome not found)"; \
	fi
else
	@echo "❌ missing TAG definition"
endif

stop:
ifeq ($(status),true)
	@-docker stop $(NAME)
else
	@echo "❌ container is not running"
endif

remove:
ifeq ($(status),true)
	@docker rm $(NAME) --force
else
	@echo "❌ container is not running"
endif

kill:
ifeq ($(status),true)
	@-docker kill $(NAME)
else
	@echo "❌ container is not running"
endif

exec:
	@docker exec -it $(NAME) /bin/bash

log:
ifeq ($(status),true)
	@docker logs $(NAME)
else
	@echo "❌ container is not running"
endif

# macros
everything: clear update stan local sync prod
reimage: stop clear update test build run exec
