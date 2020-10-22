
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
	vendor/bin/phpstan analyse --memory-limit=-1

lint-twig:
	php bin/lint.twig.php src/Resources/views

lint-yaml:
	php bin/lint.yaml.php --parse-tags src/Resources/config

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
	npx encore production --color=always --no-progress

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

