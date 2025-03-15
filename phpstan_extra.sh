#!/bin/bash
#@author Fred Brooker <git@gscloud.cz>

echo 'vendor/bin/phpstan -l9 analyse -c phpstan.neon app/ApiPresenter.php'

vendor/bin/phpstan -l9 analyse -c phpstan.neon app/ApiPresenter.php
