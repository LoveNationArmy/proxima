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
		| sed \
			-e '/module/{i <script>' \
			-e 'r /dev/stdin' \
			-e 'a </script>' \
			-e 'd}' \
			-i dist.php

dev:
	@live-server --entry-file=main.html

clean:
	rm -rf signals/offers/*
	rm -rf signals/answers/*

test:
	mocha-headless

.PHONY: build dev clean test
