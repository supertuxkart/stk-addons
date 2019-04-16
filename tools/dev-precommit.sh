#!/usr/bin/env bash
./vendor/bin/phpunit --configuration phpunit.xml
composer phpcs
composer phpstan
