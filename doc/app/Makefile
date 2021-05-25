.SILENT:
.PHONY: build

###########
# Install #
###########

# Install dependencies
install:
	composer install
	npm install

###############
# Development #
###############

# Start server
start:
	symfony server:start --no-tls

clear:
	rm -rf build public/build

# Launch watch
watch:
	npm run watch

#########
# Build #
#########

## Build assets
build:
	npm run build

## Build static site with assets
build-static: build build-content

## Build static site
build-content: export APP_ENV = prod
build-content:
	bin/console cache:clear
	bin/console stenope:build

## Build the site and serve the static version
serve-static: build-content
	open http://localhost:8032
	php -S localhost:8032 -t build

## Simulates GH Pages deploy into a subdir / with base url
build-subdir: export APP_ENV = prod
build-subdir: export WEBPACK_PUBLIC_PATH = /stenope/build
build-subdir: export ROUTER_DEFAULT_URI = http://localhost:8032/stenope
build-subdir: clear build
	rm -rf public/resized
	bin/console cache:clear
	bin/console stenope:build build/stenope

## Serve the static version of the site from a subdir / with base url
serve-static-subdir:
	open http://localhost:8032/stenope
	php -S localhost:8032 -t build
