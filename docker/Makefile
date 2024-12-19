#@author Fred Brooker <git@gscloud.cz>
MAKEFLAGS += --no-print-directory

all: info

info:
	@echo "\e[1;32m👾 Welcome to Tesseract\e[0m"
	@echo ""
	@echo "-\e[0;1m doctor\e[0m - run doctor"
	@echo "-\e[0;1m refresh\e[0m - refresh cloud data"
	@echo "-\e[0;1m test\e[0m - local tests"

doctor:
	@bash ./cli.sh doctor

refresh:
	@./cli.sh refresh
	@./cli.sh clearcache

test:
	@service apache2 start && ./cli.sh local
