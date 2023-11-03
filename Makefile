#@author Fred Brooker <git@gscloud.cz>
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
	@echo "🆘 \e[0;1mmake du\e[0m - update container"
	@echo ""
	@echo "🆘 \e[0;1mmake install\e[0m - install"
	@echo ""
	@echo "🆘 \e[0;1mmake clear\e[0m - clear all temporary files"
	@echo "🆘 \e[0;1mmake clearcache\e[0m - clear cache"
	@echo "🆘 \e[0;1mmake clearci\e[0m - clear CI logs"
	@echo "🆘 \e[0;1mmake clearlogs\e[0m - clear logs"
	@echo "🆘 \e[0;1mmake cleartemp\e[0m - clear temp"
	@echo ""
	@echo "🆘 \e[0;1mmake doctor\e[0m - run Tesseract doctor"
	@echo "🆘 \e[0;1mmake update\e[0m - update dependencies"
	@echo "🆘 \e[0;1mmake test\e[0m - run local integration test"
	@echo "🆘 \e[0;1mmake prod\e[0m - run production integration test"
	@echo "🆘 \e[0;1mmake unit\e[0m - run unit test"
	@echo "🆘 \e[0;1mmake sync\e[0m - sync to the remote"
	@echo ""
	@echo "🆘 \e[0;1mmake docs\e[0m - build documentation"
	@echo ""
	@echo "🆘 \e[0;1mmake everything\e[0m - macro: doctor clear update test sync prod"
	@echo "🆘 \e[0;1mmake image\e[0m - macro: doctor clear update test build run"
	@echo ""

docs:
	@echo "🔨 \e[1;32m Creating documentation\e[0m\n"
	@bash ./bin/create_pdf.sh

update:
	@bash ./bin/update.sh
	@make clear
	@echo ""

unit:
	@bash ./cli.sh unit

clear:
	@bash ./cli.sh clearall

clearcache:
	@bash ./cli.sh clearcache

clearci:
	@bash ./cli.sh clearci

clearlogs:
	@bash ./cli.sh clearlogs

cleartemp:
	@bash ./cli.sh cleartemp

install:
	@bash ./bin/install.sh

doctor:
	@bash ./cli.sh doctor

sync:
	@bash ./bin/sync.sh x
	@bash ./bin/sync.sh b
	@bash ./bin/sync.sh a

local: test

test: unit
ifneq ($(strip $(has_phpstan)),)
	phpstan -l9 analyse -c phpstan.neon www/index.php Bootstrap.php app/CiTester.php app/CorePresenter.php app/CliDemo.php app/CliVersion.php app/CliVersionjson.php app/Doctor.php app/ErrorPresenter.php app/HomePresenter.php app/UnitTester.php app/ArticlePresenter.php app/LogoutPresenter.php app/RSSPresenter.php
endif
ifneq ($(strip $(PHPSTAN_EXTRA)),)
	@bash ./phpstan_extra.sh
endif
	@bash ./cli.sh local

prod:
	@bash ./cli.sh unit
	@bash ./cli.sh prod

build:
	@echo "🔨 \e[1;32m Building image\e[0m\n"
	@bash ./bin/build.sh

push:
	@echo "🔨 \e[1;32m Pushing image to Docker.io\e[0m\n"
	@bash ./bin/push.sh

run:
	@echo "🔨 \e[1;32m Running container\e[0m\n"
	@bash ./bin/run.sh

start:
	@echo "🔨 \e[1;32m Starting container\e[0m\n"
	@bash ./bin/start.sh

stop:
	@echo "🔨 \e[1;32m Stopping container\e[0m\n"
	@bash ./bin/stop.sh

kill:
	@echo "🔨 \e[1;32m Killing container\e[0m\n"
	@bash ./bin/kill.sh

execbash:
	@bash ./bin/execbash.sh

du:
	@echo "🔨 \e[1;32m Updating\e[0m\n"
	@bash ./bin/update_docker.sh

# update and test local + sync to remote and test
everything: clear update test sync prod

# build docker image
image: clear update test build run
