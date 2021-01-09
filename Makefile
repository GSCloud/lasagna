all: info

info:
	@echo "\n\e[1;32m👾 Welcome to Tesseract Lasagna 👾\n"

	@echo "🆘 \e[0;1mmake docs\e[0m - build documentation"
	@echo "🆘 \e[0;1mmake doctor\e[0m - Tesseract doctor"
	@echo "🆘 \e[0;1mmake install\e[0m - install/reinstall (safe)"
	@echo "🆘 \e[0;1mmake prodtest\e[0m - production integration test"
	@echo "🆘 \e[0;1mmake sync\e[0m - sync to the remote"
	@echo "🆘 \e[0;1mmake test\e[0m - local integration test"
	@echo "🆘 \e[0;1mmake update\e[0m - update dependencies\n"

docs:
	@/bin/bash ./create_pdf.sh

update:
	@/bin/bash ./UPDATE.sh

install:
	@/bin/bash ./INSTALL.sh

doctor:
	@/bin/bash ./cli.sh doctor

sync:
	@/bin/bash ./SYNC.sh x
	@/bin/bash ./SYNC.sh b

test:
	@/bin/bash ./cli.sh local

prodtest:
	@/bin/bash ./cli.sh prod

everything: docs update sync
