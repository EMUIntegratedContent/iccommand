<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Tests always run in the test env. phpunit.xml.dist forces APP_ENV into getenv() and $_ENV, but an
// APP_ENV already in the shell (e.g. from sourcing .env) stays in $_SERVER, which Dotenv reads first.
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
putenv('APP_ENV=test');

// Tests run against the same database as the app (see CLAUDE.md), so .env.local is read too:
// the [] lifts Dotenv's default of skipping it in the test env. Other real environment
// variables win over the files.
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env', 'dev', []);

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
