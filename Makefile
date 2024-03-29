.PHONY: dist

include .make/help.mk
include .make/text.mk

PHP_CS_FIXER_VERSION=v3.13.0

##########
# Colors #
##########

COLOR_RESET   := \033[0m
COLOR_ERROR   := \033[31m
COLOR_INFO    := \033[32m
COLOR_WARNING := \033[33m
COLOR_COMMENT := \033[36m

###########
# Helpers #
###########

php8:
	@php -r "exit (PHP_MAJOR_VERSION == 8 ? 0 : 1);" || ($(call message_error, Please use PHP 8) && exit 1)

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

## Install - Install Symfony 5.4 deps
install.54: setup
install.54: export SYMFONY_REQUIRE = 5.4.*@dev
install.54:
	rm -f composer.lock
	symfony composer update

## Install - Install Symfony 6.0 deps
install.60: setup
install.60: export SYMFONY_REQUIRE = 6.0.*@dev
install.60:
	rm -f composer.lock
	symfony composer update

## Install - Install Symfony 6.1 deps
install.61: setup
install.61: export SYMFONY_REQUIRE = 6.1.*@dev
install.61:
	rm -f composer.lock
	symfony composer update

## Install - Install Symfony 6.2 deps
install.62: setup
install.62: export SYMFONY_REQUIRE = 6.2.*@dev
install.62:
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

lint.php-cs-fixer: php8
lint.php-cs-fixer: php-cs-fixer.phar
lint.php-cs-fixer:
	symfony php ./php-cs-fixer.phar fix --dry-run --diff

lint.php-cs-fixer.fix: php8
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

