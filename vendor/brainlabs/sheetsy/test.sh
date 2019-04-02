#!/bin/bash

set -o errexit
set -o nounset

composer validate
vendor/bin/phpunit tests/unit
vendor/bin/phpunit tests/integration