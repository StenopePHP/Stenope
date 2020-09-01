
###########
# Install #
###########

install:
	composer install

########
# Lint #
########

lint: lint-phpcsfixer lint-phpstan lint-twig lint-yaml

fix-phpcsfixer:
	vendor/bin/php-cs-fixer fix

lint-phpcsfixer:
	vendor/bin/php-cs-fixer fix --dry-run --diff

lint-phpstan:
	vendor/bin/phpstan analyse

lint-twig:
	bin/console lint:twig Resources/views

lint-yaml:
	bin/console lint:yaml Resources/config
