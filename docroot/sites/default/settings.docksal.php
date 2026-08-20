<?php

/**
 * @file
 * Docksal environment settings. Local development only, safe to commit.
 */

$databases['default']['default'] = [
  'database' => getenv('MYSQL_DATABASE') ?: 'default',
  'username' => getenv('MYSQL_USER') ?: 'user',
  'password' => getenv('MYSQL_PASSWORD') ?: 'user',
  'host' => getenv('MYSQL_HOST') ?: 'db',
  'port' => '3306',
  'driver' => 'mysql',
  'prefix' => '',
  'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
  'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
];

$settings['hash_salt'] = 'docksal-local-development-only';
$settings['trusted_host_patterns'] = ['.*'];
$settings['file_private_path'] = '../private';
$settings['skip_permissions_hardening'] = TRUE;
$config['system.logging']['error_level'] = 'verbose';
