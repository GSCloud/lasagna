#@author Fred Brooker <git@gscloud.cz>
MAKEFLAGS += --no-print-directory
include .env
has_phpstan != command -v phpstan 2>/dev/null

all: info

info:
	@echo "\e[1;32m👾 Welcome to ${APP_NAME}"
	@echo ""
	@echo "🆘 \e[0;1mmake build\e[0m - build Docker image"
	@echo "🆘 \e[0;1mmake run\e[0m - run Docker image and show web browser"
	@echo "🆘 \e[0;1mmake push\e[0m - push Docker image into the registry"
	@echo "🆘 \e[0;1mmake start\e[0m - start container"
	@echo "🆘 \e[0;1mmake stop\e[0m - stop container"
	@echo "🆘 \e[0;1mmake kill\e[0m - kill container"
	@echo "🆘 \e[0;1mmake execbash\e[0m - exec bash in the container"
	@echo ""
	@echo "🆘 \e[0;1mmake install\e[0m - install"
	@echo ""
	@echo "🆘 \e[0;1mmake clear\e[0m - clear all temporary files"
	@echo ""
	@echo "🆘 \e[0;1mmake doctor\e[0m - run Tesseract doctor"
	@echo "🆘 \e[0;1mmake update\e[0m - update dependencies"
	@echo "🆘 \e[0;1mmake stan\e[0m - run PHPstan tests"
	@echo "🆘 \e[0;1mmake unit\e[0m - run unit test"
	@echo "🆘 \e[0;1mmake test\e[0m - run local integration test"
	@echo "🆘 \e[0;1mmake prod\e[0m - run production integration test"
	@echo "🆘 \e[0;1mmake sync\e[0m - sync to the remote"
	@echo ""
	@echo "🆘 \e[0;1mmake docs\e[0m - build documentation"
	@echo ""
	@echo "🆘 \e[0;1mmake everything\e[0m - macro: doctor clear update test sync prod"
	@echo "🆘 \e[0;1mmake image\e[0m - macro: doctor clear update test build run"
	@echo ""

docs:
	@echo "🔨 \e[1;32m Creating documentation\e[0m\n"
	@./bin/create_pdf.sh

update:
	@./bin/update.sh
	@make clear

unit:
	@./cli.sh unit

clear:
	@./cli.sh clearall

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
	@echo "Making icons"
	@cd ./www/img && ./create_favicons.sh

stan:
ifneq ($(strip $(has_phpstan)),)
	vendor/bin/phpstan -l9 analyse -c phpstan.neon www/index.php Bootstrap.php app/CiTester.php app/AdminPresenter.php app/CorePresenter.php app/CliDemo.php app/CliVersion.php app/CliVersionjson.php app/Doctor.php app/ErrorPresenter.php app/HomePresenter.php app/UnitTester.php app/ArticlePresenter.php app/LogoutPresenter.php app/RSSPresenter.php app/StringFilters.php
endif
ifneq ($(strip $(PHPSTAN_EXTRA)),)
	@./phpstan_extra.sh
endif

prod:
	@./cli.sh unit
	@./cli.sh prod

build:
	@echo "🔨 \e[1;32m Building image\e[0m\n"
	@./bin/build.sh

push:
	@echo "🔨 \e[1;32m Pushing image to Docker.io\e[0m\n"
	@./bin/push.sh

run:
	@echo "🔨 \e[1;32m Running container\e[0m\n"
	@./bin/run.sh

start:
	@echo "🔨 \e[1;32m Starting container\e[0m\n"
	@./bin/start.sh

stop:
	@echo "🔨 \e[1;32m Stopping container\e[0m\n"
	@./bin/stop.sh

kill:
	@echo "🔨 \e[1;32m Killing container\e[0m\n"
	@./bin/kill.sh

execbash:
	@./bin/execbash.sh

# update and test local + sync to remote and test
everything: clear update local test sync prod

# build docker image
image: clear update test local build run
