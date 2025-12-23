#@author Fred Brooker <git@gscloud.cz>
MAKEFLAGS += --no-print-directory
WWW_USER := www-data

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
ifeq ($(NO_COLOR),1)
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
	@echo "${L}» DEVELOPMENT${RESET}"
	@echo "${BOLD}install${RESET}\t core installation"
	@echo "${BOLD}doctor${RESET}\t check state"
	@echo "${BOLD}update${RESET}\t update dependencies"
	@echo "${BOLD}icons${RESET}\t rebuild icons"
	@echo "${BOLD}base${RESET}\t download and build base Sheets CSV"
	@echo "${BOLD}refresh${RESET}\t refresh Sheets CSV"
	@echo "${BOLD}clear${RESET}\t clear all temporary files"
	@echo "${BOLD}sync${RESET}\t sync to the remote host"
	@echo "${BOLD}docs${RESET}\t fix Sheets export for CHANGELOG.md "
	@echo ""
	@echo "${L}» TESTING${RESET}"
	@echo "${BOLD}stan${RESET}\t PHPStan tests"
	@echo "${BOLD}unit${RESET}\t UNIT tests"
	@echo "${BOLD}test${RESET}\t LOCAL integration test"
	@echo "${BOLD}prod${RESET}\t PRODUCTION integration test"
	@echo ""
	@echo "${L}» DOCKER${RESET}"
	@echo "${BOLD}build${RESET}\t build image"
	@echo "${BOLD}push${RESET}\t push image to Docker Hub"
	@echo "${BOLD}start${RESET}\t start container"
	@echo "${BOLD}run${RESET}\t start container + Chrome browser"
	@echo "${BOLD}stop${RESET}\t stop container"
	@echo "${BOLD}kill${RESET}\t kill container"
	@echo "${BOLD}exec${RESET}\t run terminal inside container"
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
	$(error "ERROR: Missing wget command!")
endif

docs:
	@sed -i -e 's/`~~\*\*/`**/g' -e 's/\* \*\*~~/* ~~**/g' -e 's/ `\*\*/`**/g' CHANGELOG.md
	@echo 'Done.'

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
	@sudo -u $(WWW_USER) -- ./cli.sh refresh
	@sudo -u $(WWW_USER) -- ./cli.sh clearcache

prod:
	@./cli.sh unit
	@./cli.sh prod

icons:
	@cd ./www/img && ./create_favicons.sh

stan:
ifneq ($(strip $(has_phpstan)),)
	@vendor/bin/phpstan -l6 analyse -c phpstan.neon \
		www/index.php \
		Bootstrap.php \
		app/App.php \
		app/APresenter.php \
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
	@echo "🔨 \e[1;32m Building image\e[0m\n"
	@bash ./bin/docker_build.sh

push:
	@echo "🔨 \e[1;32m Pushing image to Docker Hub\e[0m\n"
	@bash ./bin/docker_push.sh

run:
	@echo "🔨 \e[1;32m Running container\e[0m\n"
	@bash ./bin/docker_run.sh

start:
	@echo "🔨 \e[1;32m Starting container\e[0m\n"
	@bash ./bin/docker_start.sh

stop:
	@echo "🔨 \e[1;32m Stopping container\e[0m\n"
	@bash ./bin/docker_stop.sh

kill:
	@echo "🔨 \e[1;32m Killing container\e[0m\n"
	@bash ./bin/docker_kill.sh

exec:
	@bash ./bin/docker_exec.sh

# macros
everything: clear update stan local sync prod
