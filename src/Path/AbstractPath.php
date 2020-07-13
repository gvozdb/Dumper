<?php

namespace Gvozdb\Dumper\Path;

use Gvozdb\Dumper\Storage;
use Gvozdb\Dumper\Database;
use Gvozdb\Dumper\Compressor;

abstract class AbstractPath
{
    /**
     * @var array $config
     */
    protected $config = [];
    /**
     * @var array $files
     */
    protected $files = [];

    /**
     * @param array $config
     *
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enabled' => false,
            'src' => null,
            'dest' => null,
        ], $config);

        //
        foreach (['src', 'dest'] as $k) {
            if (empty($this->config[$k])) {
                throw new \Exception('Path config bad.');
            }
        }

        //
        foreach (['database', 'exclude'] as $k) {
            if (!isset($this->config[$k])) {
                $this->config[$k] = [];
            }
        }
    }

    /**
     * @param array|Storage\AbstractStorage $storages
     *
     * @throws \Exception
     */
    public function run(array $storages)
    {
        $this->compress($this->config['src'], $this->config['dest'], ($this->config['exclude'] ?: []));
        $this->database();
        $this->upload($storages);
        $this->clean();
    }

    /**
     *
     */
    public function isEnabled()
    {
        return $this->config['enabled'];
    }

    /**
     * @param string $src
     * @param string $dest
     * @param array  $exclude
     *
     * @throws \Exception
     */
    protected function compress($src, $dest, array $exclude = [])
    {
        //
        $compressor = new Compressor\Zip([
            'src' => $src,
            'dest' => $dest,
            'exclude' => $exclude,
        ]);
        $compressor->compress();

        //
        if ($filepath = $compressor->getFilePath()) {
            $this->files[$filepath] = false;
        }
        unset($compressor, $filepath);
    }

    /**
     *
     */
    protected function database()
    {
        //
        $config = $this->config['database'] ?: [];
        if (empty($config['type'])) {
            return;
        }

        //
        $class = ucfirst(strtolower($config['type']));
        if (!class_exists("\Gvozdb\Dumper\Database\\{$class}")) {
            return;
        }

        //
        /** @var Database\AbstractDatabase $database */
        $database = new \ReflectionClass("\Gvozdb\Dumper\Database\\{$class}");
        $database = $database->newInstance(array_merge($config, [
            'dest' => $this->config['dest'],
        ]));
        $database->export();

        //
        if ($filepath = $database->getFilePath()) {
            $this->compress($filepath, $filepath);
            @unlink($filepath);
        }

        unset($database, $filepath, $class, $config);
    }

    /**
     * @param array|Storage\AbstractStorage $storages
     */
    protected function upload($storages)
    {
        if (empty($storages) || empty($this->files)) {
            return;
        }
        if (!is_array($storages)) {
            $storages = [$storages];
        }

        foreach ($this->files as $filepath => &$status) {
            /** @var Storage\AbstractStorage $storage */
            foreach ($storages as $storage) {
                if ($storage->upload($filepath)) {
                    $status = true;
                }
            }
        }
        unset($status);
    }

    /**
     *
     */
    protected function clean()
    {
        if (empty($this->files)) {
            return;
        }
        foreach ($this->files as $filepath => $status) {
            if ($status === true) {
                @unlink($filepath);
            }
        }
    }

    /**
     * Чистит папку в файловой системе
     *
     * @param string $path
     * @param bool   $remove_self
     */
    static public function cleanDir($path, $remove_self = false)
    {
        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $v) {
                if ($v != '.' && $v != '..') {
                    if (filetype($path . '/' . $v) == 'dir') {
                        self::cleanDir($path . '/' . $v, true);
                    } else {
                        unlink($path . '/' . $v);
                    }
                }
            }
            reset($files);
            if ($remove_self) {
                rmdir($path);
            }
        } elseif (!$remove_self) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * @param string $path
     *
     * @return string
     */
    static public function prepare($path)
    {
        return preg_replace(["/\.*[\/|\\\]/i", "/[\/|\\\]+/i"], ['/', '/'], $path);
    }
}