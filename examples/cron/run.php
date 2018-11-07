<?php

use Gvozdb\Dumper;

require __DIR__ . '/../../vendor/autoload.php';

try {
    $config = new Dumper\Config\Load(__DIR__ . '/config.yaml');
    $backup = new Dumper\Backup($config);
    $backup->run();
} catch (Exception $e) {
    print_r($e->getMessage() . PHP_EOL);
}