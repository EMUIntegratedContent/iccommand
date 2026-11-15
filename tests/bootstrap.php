<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Tests run against the same database as the app (see CLAUDE.md), so .env.local is read too:
// the [] lifts Dotenv's default of skipping it in the test env. APP_ENV=test comes from
// phpunit.xml.dist, and real environment variables win over the files.
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env', 'dev', []);

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
