<?php

// Coding standard: @Symfony. Not applied to the whole codebase yet (it mixes tabs, 2 and 4
// spaces), so CI does not enforce it. Fix the files you touch:
//   vendor/bin/php-cs-fixer fix src/Path/To/File.php
//   vendor/bin/php-cs-fixer fix --dry-run --diff          # preview everything

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->exclude(['Migrations']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
    // php-cs-fixer doesn't list PHP 8.5 as supported yet; it works for our code.
    ->setUnsupportedPhpVersionAllowed(true)
    ->setCacheFile(__DIR__ . '/var/cache/.php-cs-fixer.cache');
