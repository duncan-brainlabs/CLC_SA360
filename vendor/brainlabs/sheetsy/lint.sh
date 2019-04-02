#!/bin/bash

set -o errexit
set -o nounset

composer validate
vendor/squizlabs/php_codesniffer/bin/phpcs