
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
	php bin/lint.twig.php src/Resources/views

lint-yaml:
	php bin/lint.yaml.php src/Resources/config

########
# Demo #
########

demo:
	cd tests/fixtures/app; \
		bin/console c:c; \
		bin/console content:build --no-expose; \
		open http://localhost:8000; \
		php -S localhost:8000 -t build;

########
# Test #
########

test:
	vendor/bin/simple-phpunit

