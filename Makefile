.PHONY: ecs phpstan prepush

ecs:
	vendor/bin/ecs check

phpstan:
	vendor/bin/phpstan analyse -c phpstan.neon

prepush: ecs phpstan
