# OpenEuropa Robo integration

[![Build Status](https://travis-ci.org/ec-europa/oe-robo.svg?branch=master)](https://travis-ci.org/ec-europa/oe-robo)

OpenEuropa build system based on [Robo](http://robo.li/).

## Usage

Make sure your `RoboFile` class extends `EC\OpenEuropa\Robo\Tasks`:

```php
<?php

use EC\OpenEuropa\Robo\Tasks;

/**
 * Class RoboFile.
 */
class RoboFile extends Tasks {

}
```

## Available commands

List available commands by running:

```
$ ./vendor/bin/robo
```

| Command | Description |
|---|---|
| `project:install` | Install project from scratch |
| `project:install-config` | Install project from existing configuration |
| `project:setup-settings` | Setup Drupal `settings.php` file |
| `project:setup-behat` | Setup Behat test environment |
| `project:setup-phpunit` | Setup PHPUnit test environment |

## Configuration

Build commands can be configured by providing the following configuration parameters in a `robo.yml.dist` file: 

```yaml
# Site information.
site:
  name: Site name
  mail: info@example.org
  profile: oe_profile
  update: false
  locale: en

# Administrator account.
account:
  name: admin
  password: admin
  mail: admin@example.org

# Database parameters.
database:
  host: 127.0.0.1
  port: 3306
  name: drupal
  user: root
  password: ''
  prefix: ''

# Behat settings.
behat:
  # Behat configuration template.
  source: behat.yml.dist
  # Resulting Behat configuration file after performing token replacement.
  destination: behat.yml
  # Following tokens will be automatically replaced when running "project:setup-behat".
  tokens:
    _base_url: http://localhost

# PHPUnit settings.
phpunit:
  # PHPUnit configuration template.
  source: phpunit.xml.dist
  # Local PHPUnit configuration file.
  destination: phpunit.xml
  # The path to testing bootstrap.php file.
  bootstrap: !site.webroot/core/tests/bootstrap.php
  # Test suites.
  test_suites:
    suite_name:
      # Directories to scan for tests.
      directory:
        - ./custom/modules
        - ./custom/profiles
        - ./custom/themes
      # Test files to run.
      file: {  }
  # Replace with "\Drupal\Tests\Listeners\HtmlOutputPrinter" to create HTML
  # snapshots.
  printer_class: null
  # Following tokens will be automatically replaced when running "project:setup-phpunit".
  tokens:
    '{simpletest_base_url}': http://localhost
    '{simpletest_db}': sqlite://localhost//tmp/test.sqlite
    '{browser_output_directory}': /tmp

# Binary location.
bin:
  drush: ./vendor/bin/drush

# Parameters for Drupal's settings.php.
settings:
  config_directories:
    sync: ../config/sync
```

Configuration is processed by the [Robo Config](https://github.com/nuvoleweb/robo-config) project, check its `README.md`
for more information on how to properly override configuration parameters.
