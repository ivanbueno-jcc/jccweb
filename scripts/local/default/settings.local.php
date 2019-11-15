<?php

/**
 * @file: This file provides a default starting point for settings.local.php
 *
 * We will try to add more useful default configuration options to help speed
 * up local project setup.
 *
 * If you find common settings you use locally on many projects, please
 * feel free to add them to the project and create a PR for review.
 */

/**
 * Assertions.
*
* The Drupal project primarily uses runtime assertions to enforce the
* expectations of the API by failing when incorrect calls are made by code
* under development.
*
* @see http://php.net/assert
* @see https://www.drupal.org/node/2492225
*
* If you are using PHP 7.0 it is strongly recommended that you set
* zend.assertions=1 in the PHP.ini file (It cannot be changed from .htaccess
  * or runtime) on development machines and to 0 in production.
*
* @see https://wiki.php.net/rfc/expectations
*/
assert_options(ASSERT_ACTIVE, TRUE);
\Drupal\Component\Assertion\Handle::register();

/**
 * Show all error messages, with backtrace information.
 *
 * In case the error level could not be fetched from the database, as for
 * example the database connection failed, we rely only on this value.
 */
$config['system.logging']['error_level'] = 'verbose';

/**
 * Allow test modules and themes to be installed.
 *
 * Drupal ignores test modules and themes by default for performance reasons.
 * During development it can be useful to install test extensions for debugging
 * purposes.
 */
//$settings['extension_discovery_scan_tests'] = TRUE;

/**
 * Skip file system permissions hardening.
 *
 * The system module will periodically check the permissions of your site's
 * site directory to ensure that it is not writable by the website user. For
 * sites that are managed with a version control system, this can cause problems
 * when files in that directory such as settings.php are updated, because the
 * user pulling in the changes won't have permissions to modify files in the
 * directory.
 */
$settings['skip_permissions_hardening'] = TRUE;

/**
 * Enable local development services.
 */
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/default/services.local.yml';

/**
 * Database settings: *
 */
$databases['default']['default'] = array (
  'database' => isset($_ENV['LANDO']) && $_ENV['LANDO'] == 'ON' ? 'drupal8' : 'drupal',
  'username' => isset($_ENV['LANDO']) && $_ENV['LANDO'] == 'ON' ? 'drupal8' : 'drupal',
  'password' => isset($_ENV['LANDO']) && $_ENV['LANDO'] == 'ON' ? 'drupal8' : 'drupal',
  'prefix' => '',
  'host' => isset($_ENV['LANDO']) && $_ENV['LANDO'] == 'ON' ? 'database' : 'mariadb',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

$config_directories['sync'] = '../config/sync';
$settings['hash_salt'] = 't2pqE4S-2yw2dHj3hf8_Rx-XjYpyvpKMjI6VyQWfjXvY7NoJuiYppfcliyvpHaJW3exu41_IMg';

/* Private directory */
// $config['system.file']['path']['private'] = '../data/private';
// $settings['file_private_path'] = $config['system.file']['path']['private'];

$settings['http_client_config']['proxy']['http'] = 'http://proxysf.jud.ca.gov:8080';
$settings['http_client_config']['proxy']['https'] = 'http://proxysf.jud.ca.gov:8080';

