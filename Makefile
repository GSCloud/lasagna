#@author Fred Brooker <git@gscloud.cz>
MAKEFLAGS += --no-print-directory
include .env

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
ifeq ($(NO_COLOR),1)  # Check if NO_COLOR environment variable is set
	BOLD :=
	DIM :=
	RESET :=
	GREEN :=
	RED :=
	YELLOW :=
	BLUE :=
else
	BOLD := $(shell tput bold)
	DIM := $(shell tput dim)
	RESET := $(shell tput sgr0)
	GREEN := $(shell tput setaf 2)
	RED := $(shell tput setaf 1)
	YELLOW := $(shell tput setaf 3)
	BLUE := $(shell tput setaf 4)
endif

all: info
info:
	@echo "${GREEN}${BOLD}👾 ${APP_NAME}${RESET}"
	@echo ""
ifneq ($(origin NAME), undefined)
ifneq ($(origin PORT), undefined)
	@echo "${RESET}📦️ ${BOLD}TESSERACT: ${YELLOW}${NAME}${RESET}\t$(dot) \e[0;4m${NAME}${RESET} \tport: ${PORT} \t🚀 http://localhost:${PORT}\n"
endif
endif
	@echo "${L}» CONTAINER${RESET}"
	@echo "${BOLD}build${RESET}\t build image"
	@echo "${BOLD}push${RESET}\t push image into the registry"
	@echo ""
	@echo "${BOLD}start${RESET}\t start"
	@echo "${BOLD}stop${RESET}\t stop"
	@echo "${BOLD}kill${RESET}\t kill"
	@echo "${BOLD}remove${RESET}\t remove"
	@echo ""
	@echo "${BOLD}run${RESET}\t start + show in the browser"
	@echo "${BOLD}cref${RESET}\t refresh cloud CSV"
	@echo "${BOLD}exec${RESET}\t interactive shell (HINT: ${DIM}run \`make\`${RESET})"
	@echo ""
	@echo "${L}» DEVELOPMENT${RESET}"
	@echo "${BOLD}install${RESET}\t core installation"
	@echo "${BOLD}update${RESET}\t update dependencies"
	@echo "${BOLD}doctor${RESET}\t check installation"
	@echo ""
	@echo "${BOLD}icons${RESET}\t update icons"
	@echo "${BOLD}base${RESET}\t download and build base CSV"
	@echo "${BOLD}refresh${RESET}\t refresh cloud CSV"
	@echo "${BOLD}clear${RESET}\t clear temporary files"
	@echo "${BOLD}sync${RESET}\t sync to the remote host"
	@echo "${BOLD}docs${RESET}\t convert documentation"
	@echo ""
	@echo "${L}» TESTING${RESET}"
	@echo "${BOLD}stan${RESET}\t PHPStan test"
	@echo "${BOLD}unit${RESET}\t UNIT test"
	@echo "${BOLD}test${RESET}\t LOCAL integration test"
	@echo "${BOLD}prod${RESET}\t PRODUCTION integration test"
	@echo ""

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
	$(error "ERROR: Missing wget command")
endif

docs:
	@-mv "$(DOWNLOADS)/Downloads/README.md" . 2>/dev/null
	@-mv "$(DOWNLOADS)/CHANGELOG.md" . 2>/dev/null
	@sed -i -e 's/`~~\*\*/`**/g' -e 's/\* \*\*~~/* ~~**/g' -e 's/ `\*\*/`**/g' CHANGELOG.md
ifneq ($(strip $(has_docker)),)
	@find . -maxdepth 1 -iname "*.md" -exec echo "converting {} to ADOC" \; -exec docker run --rm -v "$$(pwd)":/data pandoc/core -f markdown -t asciidoc -i "{}" -o "{}.adoc" \;
	@find . -maxdepth 1 -iname "*.adoc" -exec echo "converting {} to PDF" \; -exec docker run --rm -v "$$(pwd)":/documents/ asciidoctor/docker-asciidoctor asciidoctor-pdf -a allow-uri-read -a icons=font -a icon-set=fas -d book "{}" \;
	@find . -maxdepth 1 -iname "*.adoc" -delete
ifneq ($(strip $(has_rename)),)
	@rename -f 's/\.md\././' *.md.*
endif
else
	@$(error "ERROR: Missing docker command")
endif

update:
	@./bin/update.sh
	@./cli.sh clearcache
	@./cli.sh clearci
	@./cli.sh clearlogs
	@./cli.sh cleartemp

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
	@./cli.sh refresh
	@./cli.sh clearcache

prod:
	@./cli.sh unit
	@./cli.sh prod

icons:
	@cd ./www/img && ./create_favicons.sh

stan:
ifneq ($(strip $(has_phpstan)),)
	@vendor/bin/phpstan -l9 analyse -c phpstan.neon www/index.php Bootstrap.php \
		app/App.php \
		app/AdminPresenter.php \
		app/ArticlePresenter.php \
		app/CiTester.php \
		app/CliDemo.php \
		app/CliVersion.php \
		app/CliVersionjson.php \
		app/CorePresenter.php \
		app/Doctor.php \
		app/ErrorPresenter.php \
		app/HomePresenter.php \
		app/LogoutPresenter.php \
		app/RSSPresenter.php \
		app/StringFilters.php \
		app/UnitTester.php
else
	$(error "PHPStan is not installed")
endif
ifneq ($(strip $(PHPSTAN_EXTRA)),)
	@./phpstan_extra.sh
endif

build:
ifeq ($(strip $(has_docker)),)
	$(error "Docker is not installed")
endif
ifeq ($(origin NAME), undefined)
	$(error "NAME is not defined")
endif
ifeq ($(origin PORT), undefined)
	$(error "PORT is not defined")
endif
ifeq ($(origin TAG), undefined)
	$(error "TAG is not defined")
endif
	@docker build --pull -t ${TAG} .

push:
ifeq ($(strip $(has_docker)),)
	$(error "Docker is not installed")
endif
ifeq ($(origin NAME), undefined)
	$(error "NAME is not defined")
endif
ifeq ($(origin PORT), undefined)
	$(error "PORT is not defined")
endif
ifeq ($(origin TAG), undefined)
	$(error "TAG is not defined")
endif
	@docker push ${TAG}

start:
ifeq ($(strip $(has_docker)),)
	$(error "Docker is not installed")
endif
ifneq ($(strip $(status)),)
	$(error "Container is already running")
endif
ifeq ($(origin NAME), undefined)
	$(error "NAME is not defined")
endif
ifeq ($(origin PORT), undefined)
	$(error "PORT is not defined")
endif
ifeq ($(origin TAG), undefined)
	$(error "TAG is not defined")
endif
	@echo "volumes: ${BOLD}app/config_private.neon${RESET}\n"
	@echo "docker run -d --rm --name ${NAME} -p ${PORT}:80 ${TAG}\n"
	@docker run -d --rm --name ${NAME} -p ${PORT}:80 -v "$$(pwd)/app/config_private.neon":/var/www/app/config_private.neon ${TAG}
	@echo "\n🚀 http://localhost:${PORT}\n"

run:
ifeq ($(strip $(has_docker)),)
	$(error "Docker is not installed")
endif
ifneq ($(strip $(status)),)
	$(error "Container is already running")
endif
ifeq ($(origin NAME), undefined)
	$(error "NAME is not defined")
endif
ifeq ($(origin PORT), undefined)
	$(error "PORT is not defined")
endif
ifeq ($(origin TAG), undefined)
	$(error "TAG is not defined")
endif
ifneq ($(strip $(has_chrome)),)
	@docker run -d --rm --name ${NAME} -p ${PORT}:80 -v "$$(pwd)/app/config_private.neon":/var/www/app/config_private.neon ${TAG}
	@google-chrome http://localhost:${PORT} >/dev/null 2>&1 &
else
	$(error "Google Chrome is not installed")
endif

stop:
ifeq ($(strip $(has_docker)),)
	$(error "Docker is not installed")
endif
ifeq ($(strip $(status)),)
	$(error "Container is not running")
endif
ifeq ($(origin NAME), undefined)
	$(error "NAME is not defined")
endif
ifeq ($(origin PORT), undefined)
	$(error "PORT is not defined")
endif
	@-docker stop ${NAME}

kill:
ifeq ($(strip $(has_docker)),)
	$(error "Docker is not installed")
endif
ifeq ($(strip $(status)),)
	$(error "Container is not running")
endif
ifeq ($(origin NAME), undefined)
	$(error "NAME is not defined")
endif
ifeq ($(origin PORT), undefined)
	$(error "PORT is not defined")
endif
	@-docker kill ${NAME} --force

remove:
ifeq ($(strip $(has_docker)),)
	$(error "Docker is not installed")
endif
ifeq ($(strip $(status)),)
	$(error "Container is not running")
endif
ifeq ($(origin NAME), undefined)
	$(error "NAME is not defined")
endif
ifeq ($(origin PORT), undefined)
	$(error "PORT is not defined")
endif
	@-docker remove ${NAME} --force

exec:
ifeq ($(strip $(has_docker)),)
	$(error "Docker is not installed")
endif
ifeq ($(strip $(status)),)
	$(error "Container is not running")
endif
ifeq ($(origin NAME), undefined)
	$(error "NAME is not defined")
endif
ifeq ($(origin PORT), undefined)
	$(error "PORT is not defined")
endif
	@docker exec -it ${NAME} /bin/bash

cref:
ifeq ($(strip $(has_docker)),)
	$(error "Docker is not installed")
endif
ifeq ($(strip $(status)),)
	$(error "Container is not running")
endif
ifeq ($(origin NAME), undefined)
	$(error "NAME is not defined")
endif
ifeq ($(origin PORT), undefined)
	$(error "PORT is not defined")
endif
	@docker exec -it ${NAME} make refresh

# macros
everything: clear update stan local sync prod
image: clear stan build
full: everything image push
	@git push origin
	@git push hub
