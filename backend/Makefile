.PHONY: test codestyle phpstan devcontainer

test:
	@php test/setup-test-db.php 2>/dev/null || true
	vendor/bin/phpunit --color=always --no-coverage test/

codestyle:
	vendor/bin/php-cs-fixer check --config=.php-cs-fixer.php .

phpstan:
	vendor/bin/phpstan analyse --memory-limit=4G --configuration=phpstan.neon --no-progress --no-interaction src/ test/

devcontainer:
	docker build -t power-modules-devcontainer -f DockerfileDevContainer .