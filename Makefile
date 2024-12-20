#@author Fred Brooker <git@gscloud.cz>
MAKEFLAGS += --no-print-directory
include .env
has_phpstan != command -v phpstan 2>/dev/null

BASE = 'app/base.csv'
DEFAULT_FILE := $(shell mktemp)
ADMIN_FILE := $(shell mktemp)
DEFAULT_URL = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vRrx4arHlU3KLpy3Vlw_sX9iEZz2t_gZz5SV4NFa8ufcFqbVo1Cxgsp4J81-Z02cPNPJ9Jc7b_Qy_ay/pub?output=csv'
ADMIN_URL = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vRDwThuqEPGHzRWCJNs3KRy1OO8gh_t0qMRH2e5N2Ok_dSf29tqxnAImE4pnc8B4qE_2ZJKgHIiyIIk/pub?output=csv'

status != docker inspect --format '{{json .State.Running}}' ${NAME} 2>/dev/null | grep true
ifneq ($(strip $(status)),)
dot=🟢
else
dot=🔴
endif

all: info
info:
	@echo "\n\e[1;32m👾 Welcome to ${APP_NAME}\e[0m"
	@echo ""
	@echo "\e[0;1m📦️ TESSERACT\e[0m\t$(dot) \e[0;4m${NAME}\e[0m \tport: ${PORT} \t🚀 http://localhost:${PORT}"
	@echo ""
	@echo "\e[0;1mbuild\e[0m\t build image"
	@echo "\e[0;1mstart\e[0m\t start container"
	@echo "\e[0;1mstop\e[0m\t stop container"
	@echo "\e[0;1mkill\e[0m\t kill container"
	@echo "\e[0;1mrun\e[0m\t start container + show in the browser"
	@echo "\e[0;1mpush\e[0m\t push image into the registry"
	@echo "\e[0;1mexec\e[0m\t run interactive shell"
	@echo ""
	@echo "\e[0;1minstall\e[0m\t install"
	@echo "\e[0;1mdoctor\e[0m\t run Doctor"
	@echo "\e[0;1mupdate\e[0m\t update dependencies"
	@echo "\e[0;1micons\e[0m\t update icons"
	@echo "\e[0;1mbase\e[0m\t download base CSV data"
	@echo "\e[0;1mrefresh\e[0m\t refresh cloud CSV data"
	@echo "\e[0;1mclear\e[0m\t clear all temporary files"
	@echo "\e[0;1msync\e[0m\t sync to the remote host"
	@echo ""
	@echo "\e[0;1mstan\e[0m\t run PHPStan tests"
	@echo "\e[0;1munit\e[0m\t run UNIT tests"
	@echo "\e[0;1mtest\e[0m\t run LOCAL integration tests"
	@echo "\e[0;1mprod\e[0m\t run PRODUCTION integration tests"
	@echo "\e[0;1mdocs\e[0m\t transpile documentation"
	@echo ""

base:
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

docs:
	@find . -maxdepth 1 -iname "*.md" -exec echo "converting {} to ADOC" \; -exec docker run --rm -v "$$(pwd)":/data pandoc/core -f markdown -t asciidoc -i "{}" -o "{}.adoc" \;
	@find . -maxdepth 1 -iname "*.adoc" -exec echo "converting {} to PDF" \; -exec docker run --rm -v $$(pwd):/documents/ asciidoctor/docker-asciidoctor asciidoctor-pdf -a allow-uri-read -d book "{}" \;
	@find . -maxdepth 1 -iname "*.adoc" -delete

update:
	@./bin/update.sh
	@make clear

unit:
	@./cli.sh unit

clear:
	@./cli.sh clear

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

icons:
	@cd ./www/img && ./create_favicons.sh

stan:
ifneq ($(strip $(has_phpstan)),)
	@vendor/bin/phpstan -l9 analyse -c phpstan.neon www/index.php Bootstrap.php \
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
endif
ifneq ($(strip $(PHPSTAN_EXTRA)),)
	@./phpstan_extra.sh
endif

refresh:
	@./cli.sh refresh
	@./cli.sh clearcache

prod:
	@./cli.sh unit
	@./cli.sh prod

build:
	@./bin/build.sh

push:
	@./bin/push.sh

run:
	@./bin/run.sh

start:
	@./bin/start.sh

stop:
	@./bin/stop.sh

kill:
	@./bin/kill.sh

exec:
	@./bin/execbash.sh

# macro
everything: clear update stan local test sync prod

# macro
image: clear update stan local test build
