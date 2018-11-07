# Dumper

...

## Install

Via Composer

``` bash
$ composer require gvozdb/dumper
```

## Usage

``` php
$config = new \Gvozdb\Dumper\Config\Load('/path/to/config.yaml');
$backup = new \Gvozdb\Dumper\Backup($config);
$backup->run();
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

If you discover any security related issues, please email pavelgvozdb@yandex.ru instead of using the issue tracker.

## Credits

- [Pavel Gvozdb][link-author]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[link-author]: https://github.com/gvozdb
