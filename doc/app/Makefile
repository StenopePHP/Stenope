.SILENT:
.PHONY: test

# Start server
start:
	symfony server:start

# Install dependencies
install:
	composer install
	npm install

#########
# Build #
#########

## Build static site with assets
build: build-assets build-content

## Build assets
build-assets:
	npm run build

## Build static site
build-content: export APP_ENV = prod
build-content:
	bin/console cache:clear
	bin/console stenope:build

## Build the site and serve the static version
serve-static: build-content
	open http://localhost:8032
	php -S localhost:8032 -t build

# Launch watch
watch:
	npm run watch
