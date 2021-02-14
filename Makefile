all: info

info:
	@echo "\n\e[1;32m👾 Welcome to Tesseract 👾\n"

	@echo "🆘 \e[0;1mmake build\e[0m - build Docker image"
	@echo "🆘 \e[0;1mmake dd\e[0m - update Docker data"
	@echo "🆘 \e[0;1mmake push\e[0m - push image into the registry"
	@echo "🆘 \e[0;1mmake testrun\e[0m - test Docker image"

	@echo ""

	@echo "🆘 \e[0;1mmake docs\e[0m - build documentation"
	@echo "🆘 \e[0;1mmake doctor\e[0m - Tesseract doctor"
	@echo "🆘 \e[0;1mmake install\e[0m - install/reinstall (safe)"
	@echo "🆘 \e[0;1mmake prodtest\e[0m - production integration test"
	@echo "🆘 \e[0;1mmake sync\e[0m - sync to the remote"
	@echo "🆘 \e[0;1mmake test\e[0m - local integration test"
	@echo "🆘 \e[0;1mmake update\e[0m - update dependencies\n"

docs:
	@/bin/bash ./bin/create_pdf.sh

update:
	@/bin/bash ./bin/update.sh

install:
	@/bin/bash ./bin/install.sh

doctor:
	@/bin/bash ./cli.sh doctor

sync:
	@/bin/bash ./bin/sync.sh x
	@/bin/bash ./bin/sync.sh b

test:
	@/bin/bash ./cli.sh local

prodtest:
	@/bin/bash ./cli.sh prod

build:
	@echo "\n🔨 \e[1;32m Building Docker image\e[0m"
	@/bin/bash ./bin/build.sh

push:
	@echo "\n🔨 \e[1;32m Pushing image to DockerHub\e[0m"
	@docker push gscloudcz/tesseract-lasagna:latest

testrun:
	@echo "\n🔨 \e[1;32m Testing Docker image\e[0m"
	@/bin/bash ./bin/testrun.sh

dd:
	docker exec tesseract bash ./docker_updater.sh

everything: docs update sync
