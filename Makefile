build:
	@cat main.html \
		| sed \
			-e '/include/{i ?>' \
			-e 'r /dev/stdin' \
			-e 'a <?php' \
			-e 'd}' \
			index.php >dist.php
	@cat main.css \
		| sed \
			-e '/stylesheet/{i <style>' \
			-e 'r /dev/stdin' \
			-e 'a </style>' \
			-e 'd}' \
			-i dist.php
	@rollup main.js --silent --format iife \
		| terser \
		| sed \
			-e '/module/{i <script>' \
			-e 'r /dev/stdin' \
			-e 'a </script>' \
			-e 'd}' \
			-i dist.php

dev:
	@live-server --ignorePattern=signals --entry-file=main.html

dev-https:
	@live-server --https=/home/stagas/.nvm/versions/node/v12.9.1/lib/node_modules/live-server-https --ignorePattern=signals --entry-file=main.html

php: clean build
	@php -S 0.0.0.0:1337 dist.php

php-https: clean build stunnel.pem
	@php -S 0.0.0.0:1337 dist.php & sudo stunnel3 -d 443 -r 1337 -p ./stunnel.pem -f

stunnel.pem:
	@openssl req -new -x509 -days 365 -nodes -out stunnel.pem -keyout stunnel.pem

clean:
	rm -rf signals/offers/*
	rm -rf signals/answers/*

test:
	@mocha-headless

test-cov:
	@mocha-headless --coverage

.PHONY: build dev dev-https clean test test-cov
