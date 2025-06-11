# Testing instructions

This page is intended for developers and testers.


## PHPUnit tests

### Creating a PHPUnit test environment

1. Spawn a shell inside your Moodle installation root directory:
   ```text
   cd /usr/share/nginx/www/moodle/
   ```
2. Prepare the Moodle PHPUnit configuration. Add the following lines to your
   `config.php`:
   ```php title="config.php"
   <?php
   $CFG->phpunit_prefix = 'phpu_';
   $CFG->phpunit_dataroot = '/path/to/your/phpunit_moodledata';
   ```
3. Download [composer](https://getcomposer.org/) and install dev dependencies:
   ```text
   wget https://getcomposer.org/download/latest-stable/composer.phar
   php composer.phar install
   ```
4. Bootstrap the test environment:
   ```text
   php admin/tool/phpunit/cli/init.php --disable-composer
   ```

See: [https://moodledev.io/general/development/tools/phpunit](https://moodledev.io/general/development/tools/phpunit)


### Running tests

After you have sucessfully [created a PHPUnit envirnoment](#creating-a-phpunit-test-environment),
you can run the tests using the following commands:

- Running all tests:
  ```text
  vendor/bin/phpunit --colors --testdox
  ```
- Running all tests for a single component:
  ```text
  vendor/bin/phpunit --colors --testdox -v --filter "tool_userautodelete"
  ```
- Running a single test suite:
  ```text
  vendor/bin/phpunit --colors --testdox -v admin/tool/userautodelete/tests/manager_test.php
  ```
  
- Running data privacy compliance test suites:
  ```text
  vendor/bin/phpunit --colors --testdox -v --testsuite tool_dataprivacy_testsuite,tool_policy_testsuite,core_privacy_testsuite
  ```

**Attention:** All commands must be run from inside your Moodle root directory.


## Code coverage

### Prerequisites

To generate code coverage reports, you need to have:

1. your [PHPUnit test environment](#phpunit-tests) set up.
2. the `xdebug` extension installed and enabled in your PHP environment.


### Generating coverage reports

To generate code coverage reports, follow these steps:

1. Run PHPUnit with coverage report:
   ```text
   XDEBUG_MODE=coverage vendor/bin/phpunit --colors --testdox -v --coverage-html /tmp/coverage --filter "tool_userautodelete"
   ```
2. Open the report in your browser:
   ```text
   xdg-open /tmp/coverage/index.html
   ```

**Attention:** It can be required to purge your local `/tmp/covarage` directory between consecutive runs. If you find
changes not being reflected correctly in the report, try to delete the `/tmp/coverage` directory before running the unit
tests again.
