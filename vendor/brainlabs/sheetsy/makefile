format:
	vendor/bin/phpcbf --standard=PSR2 src tests

sniff:
	make sniff-src sniff-tests

sniff-src:
	vendor/bin/phpcs --standard=PSR2 src

sniff-tests:
	vendor/bin/phpcs --standard=PSR2 --exclude=PSR1.Classes.ClassDeclaration tests

unit:
	vendor/bin/phpunit tests/unit/

integration:
	vendor/bin/phpunit tests/integration

test-all:
	make sniff phan unit integration 

phan:
	phan

tags:
	ctags -R src tests

autoload:
	composer dumpautoload --optimize

docs: src
	phpdoc -d src -t $@

build:
	docker-compose build

jenkins-test:
	docker-compose \
	-f docker-compose.yml \
	-f docker-compose.integration.yml \
	run --rm sheetsy bash test.sh

jenkins-lint:
	docker-compose \
	-f docker-compose.yml \
	run --rm sheetsy bash lint.sh

.PHONY: all
.PHONY: phan
.PHONY: test-all
.PHONY: tags
.PHONY: autoload
.PHONY: sniff
.PHONY: format
