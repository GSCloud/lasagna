#!/bin/bash

# connect to the running container and run CSV updater
docker exec -t tesseract ./docker_updater.sh

# connect to the running container and run bash
docker exec -ti tesseract bash

# connect to the running container and run PHP CLI
#docker exec -t tesseract ./cli.sh doctor
