#@author Fred Brooker <git@gscloud.cz>

all: info

info:
	@echo "\e[1;32m👾 Welcome to Tesseract Docker 👾"
	@echo ""
	@echo "🆘 \e[0;1mmake doctor\e[0m - Tesseract doctor"
	@echo "🆘 \e[0;1mmake test\e[0m - local integration test"
	@echo ""

doctor:
	@bash ./cli.sh doctor

test:
	service apache2 start && ./cli.sh local
