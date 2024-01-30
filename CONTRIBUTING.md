# Contributing

## PHPUnit

See: https://moodledev.io/general/development/tools/phpunit

Initialize test environment:
```
php admin/tool/phpunit/cli/init.php
php admin/tool/phpunit/cli/util.php --buildcomponentconfigs
```

Run unit tests:
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
