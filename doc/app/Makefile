.SILENT:
.PHONY: build

include ../../.make/help.mk
include ../../.make/text.mk

###########
# Install #
###########

# Install dependencies
install: install.composer install.npm

install.composer:
	symfony composer install

install.npm:
	npm install

update: update.composer update.npm

update.composer:
	symfony composer update -W

update.npm:
	npm update

###############
# Development #
###############

## Dev - Start the whole application for development purposes (local only)
serve:
	# https://www.npmjs.com/package/concurrently
	npx concurrently "make serve.php" "make serve.assets" --names="Symfony,Webpack" --prefix=name --kill-others --kill-others-on-fail

## Dev - Start Symfony server
serve.php:
	symfony server:start --no-tls

## Dev - Start webpack dev server with HMR (Hot reload)
serve.assets:
	npx encore dev-server --mode=development

## Dev - Watch assets
watch.assets:
	npm run watch

clear.build:
	rm -rf build public/build

#########
# Build #
#########

## Build assets
build.assets:
	npm run build

## Build static site with assets
build.static: build.assets build.content

## Build static site
build.content: export APP_ENV = prod
build.content:
	symfony console cache:clear
	symfony console stenope:build

## Build the site and serve the static version
serve.static: build.content
	open http://localhost:8032
	symfony php -S localhost:8032 -t build

## Simulates GH Pages deploy into a subdir / with base url
build.subdir: export APP_ENV = prod
build.subdir: export WEBPACK_PUBLIC_PATH = /stenope/build
build.subdir: export ROUTER_DEFAULT_URI = http://localhost:8032/stenope
build.subdir: clear.build build.assets
	symfony console cache:clear
	symfony console stenope:build build/stenope

## Serve the static version of the site from a subdir / with base url
serve.static.subdir:
	open http://localhost:8032/stenope
	symfony php -S localhost:8032 -t build

########
# Test #
########

## Tests - Test
test: build.content
