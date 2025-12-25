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
	@echo "${B}base${R}\t download and build base Sheets CSV"
	@echo "${B}clear${R}\t clear all temporary files"
	@echo "${B}docs${R}\t fix Sheets export for CHANGELOG.md "
	@echo "${B}doctor${R}\t check state"
	@echo "${B}icons${R}\t rebuild icons"
	@echo "${B}install${R}\t core installation"
	@echo "${B}refresh${R}\t refresh Sheets CSV"
	@echo "${B}sync${R}\t sync to the remote host"
	@echo "${B}update${R}\t update dependencies"
	@echo ""
	@echo "${L}🤯 TESTING${R}"
	@echo "${B}prod${R}\t PRODUCTION integration test"
	@echo "${B}stan${R}\t PHPStan tests"
	@echo "${B}test${R}\t LOCAL integration test"
	@echo "${B}unit${R}\t UNIT tests"
	@echo ""
	@echo "${L}🐳 DOCKER${R}"
	@echo "${B}build${R}\t build image"
	@echo "${B}exec${R}\t run a Bash terminal inside the container"
	@echo "${B}kill${R}\t kill container"
	@echo "${B}push${R}\t push image to Docker Hub"
	@echo "${B}run${R}\t start container + Chrome browser"
	@echo "${B}start${R}\t start container"
	@echo "${B}stop${R}\t stop container"

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
	@-bash ./bin/docker_stop.sh

kill:
	@echo "🔨 \e[1;32m Killing container\e[0m\n"
	@-bash ./bin/docker_kill.sh

exec:
	@bash ./bin/docker_exec.sh

# macros
everything: clear update stan local sync prod
reimage: clear stop build run exec
