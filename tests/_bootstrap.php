<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

// Set test environment before bootEnv() so it loads .env.test and .env.test.local
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'test';

// Use Symfony's bootEnv() which properly loads all .env files in correct order:
// .env -> .env.local -> .env.test -> .env.test.local
// Real environment variables always take highest priority
(new Dotenv())->usePutenv()->bootEnv(dirname(__DIR__) . '/.env');

// Load Foundry for all tests
Zenstruck\Foundry\Test\UnitTestConfig::configure();
