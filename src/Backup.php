<?php

namespace Gvozdb\Dumper;

class Backup
{
    /**
     * @var Logger\Handler $log
     */
    public $log;
    /**
     * @var array $storages
     */
    public $storages = [];
    /**
     * @var array $config
     */
    protected $config = [];


    /**
     * @param Config\Load $config
     *
     * @throws \Exception
     */
    public function __construct(Config\Load $config)
    {
        $config->setDefault([
            'main.prefix' => '%Y%m%d-',
            'main.progress_bar' => false,
        ]);
        $this->config = $config->toArray();

        $this->config['compressor'] = @$this->config['compressor'] ?: [
            'class' => 'zip',
            'compress' => 7,
            'split' => 0,
            'password' => null,
        ];
        $this->config['compressor']['password'] = isset($this->config['compressor']['password'])
            ? $this->config['compressor']['password']
            : (@$this->config['main']['archive_password'] // @deprecated
                ?: null);

        $this->config['main']['prefix'] = strftime($this->config['main']['prefix']);

        //
        $this->config['path']['tmp'] = strftime($this->config['path']['tmp']);
        if (!file_exists($this->config['path']['tmp'])) {
            @mkdir($this->config['path']['tmp'], 0755, true);
        }

        //
        $this->log = new Logger\Handler(@$this->config['logs'] ?: []);
    }

    /**
     * @param array $users
     *
     * @return void|bool
     *
     * @throws \Exception
     */
    public function run(array $users = [])
    {
        if (!is_dir($this->config['path']['users'])) {
            $this->log->emergency('Directory with users has been not found.');
            return false;
        }

        $this->log->notice('%title% Dumper is on!');

        $tasks = [];
        $prefix = $this->config['main']['prefix'];

        //
        if ($dir = scandir($this->config['path']['users'])) {
            foreach ($dir as $k) {
                if (in_array($k, ['.', '..'], true) || (!empty($users) && !in_array($k, $users, true))) {
                    continue;
                }

                $path = $this->config['path']['users'] . $k;

                try {
                    $config = new Config\Load($path . '/dumper.yaml', [
                        'key' => $k,
                        'src' => $path,
                        'dest' => $this->config['path']['tmp'] . $prefix . 'www-' . $k,
                        'compressor' => $this->config['compressor'],
                    ]);
                    $config = $config->toArray();
                } catch (\Exception $e) {
                    $this->log->error("Could not read user config `{$k}`. Message: " . $e->getMessage());
                    continue;
                }

                try {
                    $task = new Path\User($this, $config);
                    if ($task->enabled()) {
                        $tasks[] = $task;
                    }
                    unset($task, $config, $path);
                } catch (\Exception $e) {
                    $this->log->error($e->getMessage());
                    continue;
                }
            }
        }
        unset($dir);

        //
        foreach (['root', 'etc'] as $k) {
            if (!empty($this->config['path'][$k])) {
                try {
                    $tasks[] = new Path\System($this, [
                        'enabled' => true,
                        'key' => $k,
                        'src' => $this->config['path'][$k],
                        'dest' => $this->config['path']['tmp'] . $prefix . $k,
                        'compressor' => $this->config['compressor'],
                    ]);
                } catch (\Exception $e) {
                    $this->log->error($e->getMessage());
                }
            }
        }

        //
        if (!empty($this->config['path']['log'])) {
            try {
                $tasks[] = new Path\Log($this, [
                    'enabled' => true,
                    'key' => 'log',
                    'src' => $this->config['path']['log'],
                    'dest' => $this->config['path']['tmp'] . $prefix . 'log',
                    'compressor' => $this->config['compressor'],
                    'clean_logs' => $this->config['main']['clean_logs'],
                ]);
            } catch (\Exception $e) {
                $this->log->error($e->getMessage());
            }
        }

        //
        foreach ($this->config['storages'] as $class => $config) {
            if (!class_exists("\Gvozdb\Dumper\Storage\\{$class}")) {
                $this->log->error("Storage handler `{$class}` not found.");
                continue;
            }
            try {
                $storage = new \ReflectionClass("\Gvozdb\Dumper\Storage\\{$class}");
                $storageInstance = $storage->newInstance($this, array_merge($config, [
                    'expires' => @$this->config['expires'] ?: $config['expires'],
                ]));
                if ($storageInstance->enabled()) {
                    $this->storages[$class] = $storageInstance;
                }
            } catch (\Exception $e) {
                $this->log->error("It was not possible to initialize the storage instance `{$class}`. Message: " . $e->getMessage());
            }
        }
        unset($class, $config, $storage, $storageInstance);

        //
        /** @var Path\AbstractPath $task */
        foreach ($tasks as $task) {
            $task->run();
        }

        $this->clean();

        $this->log->notice('%title% Dumper is off!');

        $this->log->bufferReset();

        return true;
    }


    /**
     *
     */
    protected function clean()
    {
        //
        if (!empty($this->storages)) {
            /** @var Storage\AbstractStorage $storageInstance */
            foreach ($this->storages as $storageClass => $storageInstance) {
                try {
                    if ($storageInstance->clean() === true) {
                        $this->log->info("Successfully cleaning old backups in the storage `{$storageClass}`.");
                    }
                } catch (\Exception $e) {
                    $this->log->error("Could not remove old backups in the storage `{$storageClass}`. Message: " . $e->getMessage());
                }
            }
        }

        // Remove temp dir
        Path\AbstractPath::cleanDir($this->config['path']['tmp'], true);
    }


    /**
     * Print progress bar
     *
     * @param int|float $actual
     * @param int|float $total
     * @param string $format
     *
     * @return int
     */
    public function progressBar($actual, $total, $format = '')
    {
        if (empty($this->config['main']['progress_bar'])) {
            return false;
        }

        $width = 30;
        $percent = floor(($actual * 100) / $total);
        $bar_percent = ceil(($width * $percent) / 100);
        $output = sprintf("%s%%[%s>%s] %s\r", $percent, str_repeat("=", $bar_percent), str_repeat(" ", $width - $bar_percent), $format);
        // $output = str_pad($output, 160, ' ', STR_PAD_RIGHT);
        fwrite(STDOUT, $output);

        return $percent;
    }
}
