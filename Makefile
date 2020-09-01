
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
	php bin/lint.twig.php Resources/views

lint-yaml:
	php bin/lint.yaml.php Resources/config
