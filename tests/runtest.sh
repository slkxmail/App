#!/bin/sh

export ZENDLIB_PATH=/Users/meniam/Sites/vendor/zf2/library

phpunit --group nnn1 --configuration phpunit.xml
#phpunit --coverage-html=./report --configuration phpunit.xml
