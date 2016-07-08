<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * User: John L. Diaz
 * Email: jdiaz@secaudit.co
 * Date: 28/01/16
 * Time: 9:42 PM
 *
 * Production configuration for the current Service
 *
 *
 * @author John L. Diaz, jdiaz@secaudit.co
 */
date_default_timezone_set('America/Bogota');
define("ROOT_PATH", __DIR__ . "/..");

$app['log.level'] = \Monolog\Logger::ERROR;
$app['debug'] = true;
$app['api.version'] = "v1";
$app['api.endpoint'] = "/api";
$app['db.config'] = array(
    "db.options" => array(
        "driver"   => "pdo_mysql",
        "dbname"   => "test_db_api",
        "host"     => "127.0.0.1",
        "user"     => "root",
        "password" => "0000",
        "charset"  => "utf8",
        "port"     => 3306,
        "options"  => array(1002 => "SET NAMES 'UTF8' COLLATE 'utf8_unicode_ci'")
    )
);
$app['global.config'] = array(
    'timeZone'         => 'America/Bogota',
    'dateFormat'       => 'Y-m-d',
    'lang'             => 'es',
    'default_country'  => 'CO',
    'yandex_api_token' => '-----',
    'max_results'      => 1000
);
$app['route_workspace'] = 'Rest';
/**
 * Register any new services for endpoints
 * Composite class names like CamelCase, must be mapped to hyphens camel-case
 *
 */
$app['route_mapping'] = array(
    'countries'      => 'Countries',
    'customers'      => 'Customers',
    'language'       => 'Language',
    'login'          => 'Login',
    'users'          => 'Users'
);
