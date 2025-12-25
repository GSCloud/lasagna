#@author Fred Brooker <git@gscloud.cz>
MAKEFLAGS += --no-print-directory
WWW_USER := www-data

# color definitions
ifeq ($(NO_COLOR),1)
	B :=
	L :=
	R :=
	GREEN :=
	RED :=
	YELLOW :=
	BLUE :=
else
	B := $(shell tput bold)
	L := $(shell tput dim)
	R := $(shell tput sgr0)
	GREEN := $(shell tput setaf 2)
	RED := $(shell tput setaf 1)
	YELLOW := $(shell tput setaf 3)
	BLUE := $(shell tput setaf 4)
endif

all: info
info:
	@echo "${GREEN}${B}👾 Tesseract LASAGNA${R}"
	@echo ""
	@echo "${B}doctor${R}\t check installation"
	@echo "${B}refresh${R}\t refresh cloud CSV"
	@echo "${B}clear${R}\t clear temporary files"
	@echo ""
	@echo "${B}unit${R}\t UNIT test"
	@echo "${B}test${R}\t LOCAL integration test"
	@echo ""
	@echo "${L}run ${B}./cli.sh${R}${L} for see other commands${R}"

doctor:
	@./cli.sh doctor

refresh:
	@./cli.sh refresh
	@./cli.sh clearcache

clear:
	@./cli.sh clearcache
	@./cli.sh clearci
	@./cli.sh clearlogs
	@./cli.sh cleartemp

test:
	@./cli.sh unit
	@./cli.sh local

unit:
	@./cli.sh unit
