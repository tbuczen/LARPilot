.PHONY: ecs phpstan phpunit prepush

ecs:
	vendor/bin/ecs check

phpstan:
	vendor/bin/phpstan analyse -c phpstan.neon

phpunit:
	vendor/bin/phpunit -c phpunit.xml.dist

prepush: ecs phpstan phpunit
