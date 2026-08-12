# Contributing

## Local development environment

This fork is developed inside the `moodle-dev` multi-version Docker environment,
where the whole CI pipeline runs locally:

```
mdl ci moodle-message_localmail                  # everything GitHub Actions runs
mdl ci moodle-message_localmail --only phpcs,phpdoc
mdl phpunit m502 message_localmail               # targeted tests
```

The instructions below describe the plain upstream workflow, for contributors
working without that environment.

## PHPUnit

See: https://moodledev.io/general/development/tools/phpunit

Initialize test environment:
```
php admin/tool/phpunit/cli/init.php
php admin/tool/phpunit/cli/util.php --buildcomponentconfigs
```

Run unit tests (on Moodle 5.1 and later the plugin lives under `public/`, so use
`public/message/output/localmail` in the paths below):
```
vendor/bin/phpunit -c message/output/localmail
```

Run unit tests and generate code coverage report:
```
php -dpcov.enabled=1 vendor/bin/phpunit -c message/output/localmail \
    --coverage-html=message/output/localmail/coverage
```

## PHP CodeSniffer

See: https://moodledev.io/general/development/tools/phpcs

Install latest Moodle rules:
```
composer global config minimum-stability dev
composer global require moodlehq/moodle-cs
```

Check code:
```
cd message/output/localmail
phpcs .
```

## Changelog file

Changelog file uses the format from [Keep a Changelog](https://keepachangelog.com).
