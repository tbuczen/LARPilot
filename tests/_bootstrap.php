<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

// Ensure APP_ENV is set to 'test' before loading environment
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
putenv('APP_ENV=test');

// Manually load environment files  with usePutenv to ensure they're available everywhere
$dotenv = new Dotenv();
$projectDir = dirname(__DIR__);

// Load .env file first
if (file_exists($projectDir . '/.env')) {
    $dotenv->usePutenv()->load($projectDir . '/.env');
}

// Then load .env.test which will override
if (file_exists($projectDir . '/.env.test')) {
    $dotenv->usePutenv()->overload($projectDir . '/.env.test');
}

// Explicitly populate $_SERVER for Symfony
foreach ($_ENV as $key => $value) {
    if (!isset($_SERVER[$key])) {
        $_SERVER[$key] = $value;
    }
}

// Load Foundry for all tests
Zenstruck\Foundry\Test\UnitTestConfig::configure();
