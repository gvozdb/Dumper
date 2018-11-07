<?php

use Gvozdb\Dumper;
use Gvozdb\Dumper\Config;

require __DIR__ . '/../../vendor/autoload.php';

try {
    $config = new Config\Load(__DIR__ . '/config.yaml');
    $backup = new Dumper\Backup($config);
    $backup->run();
} catch (Exception $e) {
    print_r($e->getMessage() . PHP_EOL);
}