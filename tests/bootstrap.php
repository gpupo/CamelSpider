<?php

date_default_timezone_set('UTC');

$loader = @include __DIR__ . '/../vendor/autoload.php';

if (!$loader) {
    die(<<<'EOT'
Before you run your tests, you need set up the project dependencies.
Run the following commands (in the project root):

    wget http://getcomposer.org/composer.phar;
    php composer.phar install;

EOT
    );
}
