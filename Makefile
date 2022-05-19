.PHONY: dist

PHP_CS_FIXER_VERSION=v3.3.2

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

define message_error
	printf "$(COLOR_ERROR)(╯°□°)╯︵ ┻━┻ $(strip $(1))$(COLOR_RESET)\n"
endef

php8:
	@php -r "exit (PHP_MAJOR_VERSION == 8 ? 0 : 1);" || ($(call message_error, Please use PHP 8) && exit 1)

###########
# Install #
###########

setup:
	composer global require --no-progress --no-scripts --no-plugins symfony/flex

install: setup
install:
	rm -f composer.lock
	composer config minimum-stability --unset
	composer update --prefer-dist

install-54: setup
install-54: export SYMFONY_REQUIRE = 5.4.*@dev
install-54:
	rm -f composer.lock
	composer update

install-60: setup
install-60: export SYMFONY_REQUIRE = 6.0.*@dev
install-60:
	rm -f composer.lock
	composer update

install-61: setup
install-61: export SYMFONY_REQUIRE = 6.1.*@dev
install-61:
	rm -f composer.lock
	composer config minimum-stability RC
	composer update
	composer config minimum-stability --unset

########
# Lint #
########

lint: lint-phpcsfixer lint-phpstan lint-twig lint-yaml lint-composer

lint-composer:
	composer validate --strict

php-cs-fixer.phar:
	wget --no-verbose https://github.com/FriendsOfPHP/PHP-CS-Fixer/releases/download/${PHP_CS_FIXER_VERSION}/php-cs-fixer.phar
	chmod +x php-cs-fixer.phar

update-php-cs-fixer.phar:
	rm -f php-cs-fixer.phar
	make php-cs-fixer.phar

lint-phpcsfixer: php8
lint-phpcsfixer: php-cs-fixer.phar
lint-phpcsfixer:
	./php-cs-fixer.phar fix --dry-run --diff

fix-phpcsfixer: php8
fix-phpcsfixer: php-cs-fixer.phar
fix-phpcsfixer:
	./php-cs-fixer.phar fix

lint-phpstan:
	vendor/bin/phpstan analyse --memory-limit=-1

lint-twig:
	php bin/lint.twig.php templates
	cd tests/fixtures/app && bin/console lint:twig templates -vv

lint-yaml:
	vendor/bin/yaml-lint --parse-tags config tests/fixtures/app/config

########
# Dist #
########

dist-update:
	npm update --color=always

dist-install:
	npm install --color=always

dist-watch:
	npx encore dev --watch

dist:
	npx encore production --color

########
# Demo #
########

demo:
	cd tests/fixtures/app; \
		bin/console c:c; \
		bin/console stenope:build --no-expose; \
		open http://localhost:8000; \
		php -S localhost:8000 -t build;

########
# Test #
########

test:
	vendor/bin/simple-phpunit

