<?php

use Gvozdb\Dumper;

require __DIR__ . '/../../vendor/autoload.php';

try {
    $config = new Dumper\Config\Load(__DIR__ . '/config.yaml');
    $backup = new Dumper\Backup($config);
    $backup->run([
        // users folders, for debug
    ]);
} catch (Exception $e) {
    print_r($e->getMessage() . PHP_EOL);
}