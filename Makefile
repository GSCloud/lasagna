all: info

info:
	@echo "\e[1;32m👾 Welcome to Tesseract Lasagna 👾"
	@echo "🆘 \e[0;1mmake docs\e[0m - rebuild documentation"
	@echo "🆘 \e[0;1mmake doctor\e[0m - run Tesseract doctor"
	@echo "🆘 \e[0;1mmake everything\e[0m - run all make points"
	@echo "🆘 \e[0;1mmake install\e[0m - install"
	@echo "🆘 \e[0;1mmake sync\e[0m - sync to remote"
	@echo "🆘 \e[0;1mmake update\e[0m - update installation"

docs:
	@/bin/bash ./create_pdf.sh

update:
	@/bin/bash ./UPDATE.sh

install:
	@/bin/bash ./INSTALL.sh

doctor:
	@/bin/bash ./cli.sh doctor

sync:
	@git commit -am "sync"
	@/bin/bash ./SYNC.sh x
	@/bin/bash ./SYNC.sh b

everything: docs update sync
