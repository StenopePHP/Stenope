.PHONY: dist

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

install:
	composer update

########
# Lint #
########

lint: lint-phpcsfixer lint-phpstan lint-twig lint-yaml lint-composer

fix-phpcsfixer: php8
fix-phpcsfixer:
	vendor/bin/php-cs-fixer fix

lint-composer:
	composer validate --strict

lint-phpcsfixer: php8
lint-phpcsfixer:
	vendor/bin/php-cs-fixer fix --dry-run --diff

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

