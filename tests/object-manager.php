<?php

// Gives phpstan-doctrine the default entity manager so it can check DQL and entity mappings.
// Needs no database connection (server_version is set in doctrine.yaml).

use App\Kernel;

require __DIR__.'/bootstrap.php';

$kernel = new Kernel('test', true);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
