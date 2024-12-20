#@author Fred Brooker <git@gscloud.cz>
MAKEFLAGS += --no-print-directory

all: info

info:
	@echo "\e[1;32m👾 Welcome to Tesseract LASAGNA\e[0m"
	@echo ""
	@echo "\e[0;1mdoctor\e[0m\t run Doctor"
	@echo "\e[0;1mrefresh\e[0m\t refresh cloud CSV data"
	@echo "\e[0;1mtest\e[0m\t run local tests"
	@echo ""

doctor:
	@bash ./cli.sh doctor

refresh:
	@./cli.sh refresh
	@./cli.sh clearcache

test:
	@./cli.sh local
