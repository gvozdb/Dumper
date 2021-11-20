# Dumper
Библиотека для создания резервных копий сервера.
* Поддерживает `MySQL`
* Сжимает в `Zip`
* Выгружает на `Яндекс.Диск`

### Что выгружает
 * Юзеров, у которых есть конфиг `dumper.yaml` в корне
 * Папки `/root/` и `/etc/`
 * Логи сервера

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
    prefix: "%Y%m%d-" # префикс для названия архивов
    clean_logs: true # очищать старые логи в директории логов path.log
    progress_bar: false # показывать прогресс-бар в терминале

# Длительность хранения бекапов
expires:
    short_step: 1 # шаг, кол-во дней
    short_max_days: 4 # максимальный срок хранения (дней)
    long_step: 30 # шаг, кол-во дней
    long_max_days: 120 # максимальный срок хранения (дней)

#
path:
    tmp: '/tmp/dumper/%Y%m%d/' # временная папка на сервере
    users: '/var/www/' # директория с юзерами, файлы которых нужно бекапить
    root: '/root/' # директория root
    etc: '/etc/' # директория etc
    log: '/var/log/' # директория серверных логов

#
compressor:
    class: 'zip' # zip или zip =)
    compress: 7 # 1 – быстрая компрессия; 9 – лучшая компрессия
    split: 209715200 # разбивать архив на файлы по N байт
    password: '' # пароль на архив

#
storages:
    # Upload to YandexDisk
    YandexDisk:
        token: 'AQAAAAABEJ2-AAVH0EIr79Yz4E5dpd-7nhV1W18' # api токен хранилища
        path: 'disk:/Dumper/ServerIP/%Y%m%d/' # папка в облаке, где хранить бекапы

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
compressor.password: '' # пароль на архив
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
