#!/bin/bash

echo 'phpstan -l9 analyse -c phpstan.neon app/ApiPresenter.php'
phpstan -l9 analyse -c phpstan.neon app/ApiPresenter.php
