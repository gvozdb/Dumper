# Changelog


## [0.3.3] - 2021-12-29

* Added 4 tries to upload a file to the cloud


## [0.3.2] - 2021-11-20

* Fixed warning `array_merge(): Expected parameter 2 to be an array, null given` in `Logger\Handler`
* Fixed bug `Undefined index: expires` in `Storage\AbstractStorage`


## [0.3.1] - 2021-11-20

* Fixed composer.json, changed version of `arhitector/yandex` to 2.0.1


## [0.3.0] - 2021-11-20

* Added the ability to select the archiver (section `compressor` in the config)
* Added the ability to specify short and long term storage of backups, as well as the step of obsolescence checking (section `expires` in config)
* Added support for level of compression in `Compressor\Zip`
* Added support for splitting the archive into files of a certain size in `Compressor\Zip`
* Added progress bar when uploading files to `Storage\YandexDisk`
* Added public method `Config\Load::setDefault`
* Added public method `Backup::progressBar`
* Fixed definition of the default prefix value for archive names
* Moved to specify the archive password in `compressor.password` in the main config and in the user config
* Properties `main.expires` and `storages.{Cloud}.expires` in the main config are declared deprecated
* Property `main.archive_password` in the main config is declared deprecated
* Property `archive_password` in the user config is declared deprecated


## [0.2.2] - 2021-05-05

* Added storage support for single user
* Fixed bug `Undefined index: logs`


## [0.2.1] - 2020-07-16

* Added archive password
* Added start and end notifications
* Changed README.md


## [0.2.0] - 2020-07-14

* Refactoring
* Added output of error messages
* Added information messages output
* Added recording of logs to file
* Added sending of logs to email
* Added sending of logs to Telegram chat
* Changed README.md


## [0.1.5] - 2019-05-21

* Added param **users** for **Backup::run()**


## [0.1.4] - 2018-11-21

* Added **class_exists** for storage in `Dumper\Backup`
* Changed README.md


## [0.1.3] - 2018-11-07

* Fixed composer.json, changed version of `arhitector/yandex` to ~2.0


## [0.1.2] - 2018-11-07

* Fixed composer.json, added property `prefer-stable`


## [0.1.1] - 2018-11-07

* Changed README.md


## [0.1.0] - 2018-11-06

* First release