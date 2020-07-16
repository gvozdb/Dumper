# Dumper
Библиотека для создания резервных копий сервера.
* Поддерживает `MySQL`
* Сжимает в `Zip`
* Выгружает на `Яндекс.Диск`

### Что выгружает
 * Юзеров, у которых есть конфиг `dumper.yaml` в корне
 * Папки `/root/` и `/etc/`
 * Логи

## Установка
``` bash
$ composer require gvozdb/dumper
```

## Использование
Создаём файл cron.php
``` php
use Gvozdb\Dumper;
require __DIR__ . '/vendor/autoload.php';
try {
    $config = new Dumper\Config\Load(__DIR__ . '/config.yaml');
    $backup = new Dumper\Backup($config);
    $backup->run();
} catch (Exception $e) {
    print_r($e->getMessage() . PHP_EOL);
}
```

### config.yaml
Конфигурационный файл приложения.
```yaml
#
main:
    archive_password: "" #
    prefix: "%Y%m%d-" #
    expires: &main.expires 4 #
    clean_logs: true #

#
path:
    tmp: '/tmp/dumper/%Y%m%d/' #
    users: '/var/www/' #
    root: '/root/' #
    log: '/var/log/' #
    etc: '/etc/' #

#
storages:
    # Upload to YandexDisk
    YandexDisk:
        token: 'AQAAAAABEJ2-AAVH0EIr79Yz4E5dpd-7nhV1W18' #
        path: 'disk:/Dumper/ServerIP/%Y%m%d/' #
        expires: *main.expires #

#
logs:
    enabled: true #
    notify:
        # Print to console
        Console:
            path: 'php://stdout' #
            #level: 'info' #
            #format: "[%datetime%] [%level_name%] > %message%\n" #
            #dateFormat: 'd.m.Y H:i:s' #

        # Write to file
        File:
            path: './log/%Y%m%d.log' #
            #level: 'info' #
            #format: "[%datetime%] [%level_name%] > %message%\n" #
            #dateFormat: 'd.m.Y H:i:s' #

        # Send to email
        Email:
            host: '' #
            port: 465 #
            encryption: 'ssl' #
            username: '' #
            password: '' #
            subject: '[%d.%m.%Y] Dumper Report' #
            from: '' #
            to: '' #
            #level: 'info' #
            #format: "[%datetime%] [%level_name%] > %message%\n" #
            dateFormat: 'H:i:s' #

        # Send to telegram chat
        Telegram:
            token: '' #
            chat: '' #
            #level: 'info' #
            #format: "[%datetime%] [%level_name%] > %message%\n" #
            dateFormat: 'H:i:s' #
```
Поместить в директорию с cron.php

### dumper.yaml
Конфигурационный файл юзера.
```yaml
enabled: true # включить
archive_password: '' #
#
database:
    type: 'mysql'
    port: 3306
    host: 'localhost'
    name: 'dbname'
    user: 'dbuser'
    pass: 'dbpassword'
#
exclude: [
    '/www/core/cache/*',
  ]
```
Поместить в корневую директорию юзера.

## Credits
- [Pavel Gvozdb][link-author]

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[link-author]: https://github.com/gvozdb
