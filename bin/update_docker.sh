#!/bin/bash

# connect to container and run CSV updater
docker exec tesseract ./docker_updater.sh

# connect to container and run bash
docker exec -ti tesseract bash
