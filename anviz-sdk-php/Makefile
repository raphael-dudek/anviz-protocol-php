#!/usr/bin/make -f

.PHONY: help install test lint clean docker-build docker-up docker-down

help:
	@echo "Anviz SDK PHP - Makefile Commands"
	@echo "=================================="
	@echo "make install      - Install dependencies"
	@echo "make test         - Run unit tests"
	@echo "make lint         - Code analysis"
	@echo "make cs-check     - Code style check"
	@echo "make cs-fix       - Fix code style"
	@echo "make clean        - Clean cache and logs"
	@echo "make docker-build - Build Docker image"
	@echo "make docker-up    - Start Docker containers"
	@echo "make docker-down  - Stop Docker containers"
	@echo "make example      - Run example 1"

install:
	composer install

test:
	vendor/bin/phpunit tests/

lint:
	vendor/bin/phpstan analyse src/

cs-check:
	vendor/bin/phpcs src/ --standard=PSR12

cs-fix:
	vendor/bin/phpcbf src/ --standard=PSR12

clean:
	rm -rf logs/*.log
	rm -rf cache/*
	rm -rf .phpunit.result.cache

docker-build:
	docker-compose build

docker-up:
	docker-compose up -d

docker-down:
	docker-compose down

example:
	docker-compose exec anviz-sdk php examples/01_connect.php
