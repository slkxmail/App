<?php

define('PROJECT_PATH', dirname(__FILE__) . '/../');

// Папка с App
defined('ZENDLIB_PATH')  || define('ZENDLIB_PATH',   (getenv('ZENDLIB_PATH')   ?: PROJECT_PATH . '/vendor' ));

define('FIXTURES_PATH',   realpath(__DIR__ . '/_fixtures'));


set_include_path(realpath(PROJECT_PATH . '/library')
				 . PATH_SEPARATOR . get_include_path());

require_once 'App/Loader/Autoloader.php';

App\Loader\Autoloader::getInstance()
    ->addRule('App\\',  PROJECT_PATH . '/library', App\Loader\Autoloader::RULE_TYPE_PREFIX)
    ->addRule('AppTest\\',  __DIR__, App\Loader\Autoloader::RULE_TYPE_PREFIX)
    ->addRule('Zend\\', ZENDLIB_PATH, App\Loader\Autoloader::RULE_TYPE_PREFIX);