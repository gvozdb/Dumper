<?php

namespace Gvozdb\Dumper;

class Backup
{
    /** @var array $config */
    protected $config = [];

    /**
     * @param Config\Load $config
     */
    public function __construct(Config\Load $config)
    {
        $this->config = array_merge([
            'prefix' => date('Ymd') . '-',
        ], $config->toArray());
        $this->config['main']['prefix'] = strftime($this->config['main']['prefix']);

        //
        $this->config['path']['tmp'] = strftime($this->config['path']['tmp']);
        if (!file_exists($this->config['path']['tmp'])) {
            @mkdir($this->config['path']['tmp'], 0755, true);
        }
        // print_r($this->config);
    }

    /**
     */
    public function run()
    {
        $tasks = [];
        $prefix = $this->config['main']['prefix'];

        //
        if ($dir = scandir($this->config['path']['users'])) {
            foreach ($dir as $k) {
                if (in_array($k, ['.', '..'])) {
                    continue;
                }
                $path = $this->config['path']['users'] . $k;
                $config = new Config\Load($path . '/dumper.yaml', [
                    'src' => $path,
                    'dest' => $this->config['path']['tmp'] . $prefix . 'www-' . $k,
                ]);
                $config = $config->toArray();

                $tmp = new Path\User($config);
                if ($tmp->isEnabled()) {
                    $tasks[] = $tmp;
                }
                unset($tmp, $config, $path);
            }
        }
        unset($dir);

        //
        if (!empty($this->config['path']['log'])) {
            $tasks[] = new Path\Log([
                'enabled' => true,
                'clean_logs' => $this->config['main']['clean_logs'],
                'src' => $this->config['path']['log'],
                'dest' => $this->config['path']['tmp'] . $prefix . 'log',
            ]);
        }

        //
        foreach (['root', 'etc'] as $k) {
            if (!empty($this->config['path'][$k])) {
                $tasks[] = new Path\System([
                    'enabled' => true,
                    'src' => $this->config['path'][$k],
                    'dest' => $this->config['path']['tmp'] . $prefix . $k,
                ]);
            }
        }

        //
        $storages = [];
        foreach ($this->config['storages'] as $class => $config) {
            $storage = new \ReflectionClass("\Gvozdb\Dumper\Storage\\{$class}");
            $storages[$class] = $storage->newInstance($config);
            unset($storage);
        }

        //
        /** @var Path\AbstractPath $task */
        foreach ($tasks as $task) {
            $task->run($storages);
        }

        //
        $this->clean($storages);
    }

    /**
     * @param array $storages
     */
    protected function clean($storages = [])
    {
        //
        if (!empty($storages)) {
            /** @var Storage\AbstractStorage $storage */
            foreach ($storages as $storage) {
                $storage->clean();
            }
        }

        // Удаляем временную папку
        Path\AbstractPath::cleanDir($this->config['path']['tmp'], true);
    }
}
