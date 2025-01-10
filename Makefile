#@author Fred Brooker <git@gscloud.cz>
MAKEFLAGS += --no-print-directory
include .env

has_chrome != command -v google-chrome 2>/dev/null
has_docker != command -v docker 2>/dev/null
has_phpstan != command -v vendor/bin/phpstan 2>/dev/null
has_rename != command -v rename 2>/dev/null
has_wget != command -v wget 2>/dev/null

BASE := 'app/base.csv'
ADMIN_FILE := $(shell mktemp)
DEFAULT_FILE := $(shell mktemp)
ADMIN_URL := 'https://docs.google.com/spreadsheets/d/e/2PACX-1vRDwThuqEPGHzRWCJNs3KRy1OO8gh_t0qMRH2e5N2Ok_dSf29tqxnAImE4pnc8B4qE_2ZJKgHIiyIIk/pub?output=csv'
DEFAULT_URL := 'https://docs.google.com/spreadsheets/d/e/2PACX-1vRrx4arHlU3KLpy3Vlw_sX9iEZz2t_gZz5SV4NFa8ufcFqbVo1Cxgsp4J81-Z02cPNPJ9Jc7b_Qy_ay/pub?output=csv'

status != docker inspect --format '{{json .State.Running}}' ${NAME} 2>/dev/null | grep true

ifneq ($(strip $(status)),)
dot=🟢
else
dot=🔴
endif

B := \e[0;1m
L := \e[0;2m
R := \e[0m
GREEN  := \e[0;32m
RED    := \e[0;31m
YELLOW := \e[0;33m
BLUE   := \e[0;34m

all: info
info:
	@echo "\n\e[1;32m👾 Welcome to ${APP_NAME}${R}"
	@echo ""
ifneq ($(origin NAME), undefined)
ifneq ($(origin PORT), undefined)
	@echo "${R}📦️ TESSERACT${R}\t$(dot) \e[0;4m${NAME}${R} \tport: ${PORT} \t🚀 http://localhost:${PORT}\n"
endif
endif
	@echo "${L}» CONTAINER${R}"
	@echo "${B}build${R}\t build image"
	@echo "${B}push${R}\t push image into the registry"
	@echo "${B}start${R}\t container start"
	@echo "${B}run${R}\t container start + show in the browser"
	@echo "${B}stop${R}\t container stop"
	@echo "${B}kill${R}\t container kill"
	@echo "${B}remove${R}\t container remove"
	@echo "${B}cref${R}\t container refresh cloud CSV"
	@echo "${B}exec${R}\t run interactive shell"
	@echo ""
	@echo "${L}» DEVELOPMENT${R}"
	@echo "${B}install${R}\t install"
	@echo "${B}doctor${R}\t run Doctor"
	@echo "${B}update${R}\t update dependencies"
	@echo "${B}icons${R}\t update icons"
	@echo "${B}base${R}\t download base CSV"
	@echo "${B}refresh${R}\t refresh cloud CSV"
	@echo "${B}sync${R}\t sync to the remote host"
	@echo "${B}clear${R}\t clear all temporary files"
	@echo "${B}docs${R}\t prepare and convert documentation"
	@echo ""
	@echo "${L}» TESTS${R}"
	@echo "${B}stan${R}\t PHPStan tests"
	@echo "${B}unit${R}\t UNIT tests"
	@echo "${B}test${R}\t LOCAL integration tests"
	@echo "${B}prod${R}\t PRODUCTION integration tests"
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
	@-mv "$(HOME)/Downloads/README.md" . 2>/dev/null
	@-mv "$(HOME)/Downloads/CHANGELOG.md" . 2>/dev/null
	@sed -i 's/`~~\*\*/`**/g' CHANGELOG.md
	@sed -i 's/\* \*\*~~/* ~~**/g' CHANGELOG.md
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
	@docker stop ${NAME}

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
	@docker kill ${NAME}

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
	@docker remove ${NAME} --force

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
