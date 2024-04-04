.PHONY: dist

include .make/help.mk
include .make/text.mk

PHP_CS_FIXER_VERSION=v3.52.1

##########
# Colors #
##########

COLOR_RESET   := \033[0m
COLOR_ERROR   := \033[31m
COLOR_INFO    := \033[32m
COLOR_WARNING := \033[33m
COLOR_COMMENT := \033[36m

###########
# Install #
###########

## Install - Prepare dev env with Symfony Flex
setup:
	symfony composer global require --no-progress --no-scripts --no-plugins symfony/flex

## Install - Install
install: setup
install:
	rm -f composer.lock
	symfony composer config minimum-stability --unset
	symfony composer update --prefer-dist

## Install - Install Symfony 6.4 deps
install.64: setup
install.64: export SYMFONY_REQUIRE = 6.4.*@dev
install.64:
	rm -f composer.lock
	symfony composer config minimum-stability dev
	symfony composer update
	symfony composer config minimum-stability --unset

## Install - Install Symfony 7.0 deps
install.70: setup
install.70: export SYMFONY_REQUIRE = 7.0.*@dev
install.70:
	rm -f composer.lock
	symfony composer config minimum-stability dev
	symfony composer update
	symfony composer config minimum-stability --unset

## Install - Install Symfony 7.1 deps
install.71: setup
install.71: export SYMFONY_REQUIRE = 7.1.*@dev
install.71:
	rm -f composer.lock
	symfony composer config minimum-stability dev
	symfony composer update
	symfony composer config minimum-stability --unset

########
# Lint #
########

## Lint - Lint
lint: lint.php-cs-fixer lint.phpstan lint.twig lint.yaml lint.composer

## Lint - Fix Lint
lint.fix: lint.php-cs-fixer.fix

lint.composer:
	symfony composer validate --strict

lint.php-cs-fixer: php-cs-fixer.phar
lint.php-cs-fixer:
	symfony php ./php-cs-fixer.phar fix --dry-run --diff

lint.php-cs-fixer.fix: php-cs-fixer.phar
lint.php-cs-fixer.fix:
	symfony php ./php-cs-fixer.phar fix

lint.phpstan:
	symfony php vendor/bin/phpstan analyse --memory-limit=-1

lint.twig:
	symfony php bin/lint.twig.php templates
	cd tests/fixtures/app && bin/console lint:twig templates -vv

lint.yaml:
	symfony php vendor/bin/yaml-lint --parse-tags config tests/fixtures/app/config

## Lint - Update tools
lint.update:
	rm -f php-cs-fixer.phar
	make php-cs-fixer.phar

php-cs-fixer.phar:
	wget --no-verbose https://github.com/FriendsOfPHP/PHP-CS-Fixer/releases/download/${PHP_CS_FIXER_VERSION}/php-cs-fixer.phar
	chmod +x php-cs-fixer.phar

########
# Dist #
########

## Dist - Build dust files
dist:
	npx encore production --color

## Dist - Install dist deps
dist.install:
	npm install --color=always

## Dist - Update dist deps
dist.update:
	npm update --color=always

## Dist - Build & watch dist files
dist.watch:
	npx encore dev --watch

########
# Demo #
########

demo:
	cd tests/fixtures/app; \
		symfony console c:c; \
		symfony console stenope:build --no-expose; \
		open http://localhost:8000; \
		symfony php -S localhost:8000 -t build;

########
# Test #
########

## Tests - Test
test:
	symfony php vendor/bin/simple-phpunit

