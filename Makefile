#@author Fred Brooker <git@gscloud.cz>
MAKEFLAGS += --no-print-directory
include .env
has_phpstan != command -v phpstan 2>/dev/null

all: info

info:
	@echo "\e[1;32m👾 Welcome to ${APP_NAME}\e[0m"
	@echo ""
	@echo "- \e[0;1m build\e[0m - build image"
	@echo "- \e[0;1m start\e[0m - start container"
	@echo "- \e[0;1m stop\e[0m - stop container"
	@echo "- \e[0;1m kill\e[0m - kill container"
	@echo "- \e[0;1m run\e[0m - run container and show web browser"
	@echo "- \e[0;1m push\e[0m - push image into the registry"
	@echo "- \e[0;1m exec\e[0m - exec bash in the container"
	@echo ""
	@echo "- \e[0;1m install\e[0m - install"
	@echo "- \e[0;1m update\e[0m - update dependencies"
	@echo "- \e[0;1m icons\e[0m - update icons"
	@echo "- \e[0;1m sync\e[0m - sync to the remote host"
	@echo "- \e[0;1m doctor\e[0m - run Tesseract doctor"
	@echo "- \e[0;1m refresh\e[0m - refresh cloud data"
	@echo "- \e[0;1m clear\e[0m - clear all temporary files"
	@echo ""
	@echo "- \e[0;1m stan\e[0m - run PHPStan tests"
	@echo "- \e[0;1m unit\e[0m - run Unit tests"
	@echo "- \e[0;1m test\e[0m - run LOCAL integration tests"
	@echo "- \e[0;1m prod\e[0m - run PRODUCTION integration tests"
	@echo ""
	@echo "- \e[0;1m docs\e[0m - build documentation"

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
	vendor/bin/phpstan -l9 analyse -c phpstan.neon www/index.php Bootstrap.php app/CiTester.php app/AdminPresenter.php app/CorePresenter.php app/CliDemo.php app/CliVersion.php app/CliVersionjson.php app/Doctor.php app/ErrorPresenter.php app/HomePresenter.php app/UnitTester.php app/ArticlePresenter.php app/LogoutPresenter.php app/RSSPresenter.php app/StringFilters.php
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

# MACRO
everything: clear update stan local test sync prod

# MACRO
image: clear update stan local test build
