<?php

namespace Gvozdb\Dumper\Path;

use Gvozdb\Dumper\Backup;
use Gvozdb\Dumper\Storage;
use Gvozdb\Dumper\Database;
use Gvozdb\Dumper\Compressor;

abstract class AbstractPath
{
    /**
     * @var Backup $dumper
     */
    protected $dumper;
    /**
     * @var array $config
     */
    protected $config = [];
    /**
     * @var array $files
     */
    protected $files = [];
    /**
     * @var array $storages
     */
    protected $storages = [];


    /**
     * @param Backup $dumper
     * @param array $config
     *
     * @throws \Exception
     */
    public function __construct(Backup $dumper, array $config = [])
    {
        $this->dumper = $dumper;

        $this->config = array_merge([
            'enabled' => false,
            'src' => null,
            'dest' => null,
            'storages' => [],
        ], $config);

        // @deprecated
        $this->config['compressor']['password'] = isset($this->config['archive_password'])
            ? $this->config['archive_password']
            : (@$this->config['compressor']['password'] ?: null);

        //
        foreach (['src', 'dest', 'compressor'] as $k) {
            if (empty($this->config[$k])) {
                throw new \Exception("Incorrect config for `{$this->config['key']}`. " . print_r($this->config, 1));
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
     * @throws \Exception
     */
    public function run()
    {
        $this->compress();
        $this->database();
        $this->upload();
        $this->clean();
    }


    /**
     * @return bool
     */
    public function enabled()
    {
        foreach (['enabled', 'src', 'dest'] as $k) {
            if (empty($this->config[$k])) {
                return false;
            }
        }

        return true;
    }


    /**
     * @param null|string $src
     * @param null|string $dest
     * @param array $exclude
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function compress($src = null, $dest = null, array $exclude = [])
    {
        if (empty($src)) {
            $src = $this->config['src'];
            $dest = $this->config['dest'];
            $exclude = $this->config['exclude'] ?: [];
        }

        $config = $this->config['compressor'];
        if (empty($config['class'])) {
            return;
        }

        try {
            $class = ucfirst(strtolower($config['class']));
            if (!class_exists("\Gvozdb\Dumper\Compressor\\{$class}")) {
                throw new \Exception("Compressor handler `{$class}` not found.");
            }

            $compressor = new \ReflectionClass("\Gvozdb\Dumper\Compressor\\{$class}");
            $compressor = $compressor->newInstance(array_merge($config, [
                'key' => $this->config['key'],
                'src' => $src,
                'dest' => $dest,
                'exclude' => $exclude,
            ]));
            /** @var Compressor\AbstractCompressor $compressor */
            if ($compressor->compress() === true) {
                $src_type = is_dir($src) ? 'directory' : 'file';
                $this->dumper->log->info("Successfully compressed {$src_type} `{$src}`.");
            }

            //
            if ($files = $compressor->getFiles()) {
                foreach ($files as $file) {
                    $this->files[$file] = false;
                }
            }
            unset($compressor, $files, $file);
        } catch (\Exception $e) {
            $this->dumper->log->error($e->getMessage());
        }
    }


    /**
     * @return void
     *
     * @throws \Exception
     */
    protected function database()
    {
        $config = @$this->config['database'] ?: [];
        if (empty($config['type'])) {
            return;
        }

        try {
            $class = ucfirst(strtolower($config['type']));
            if (!class_exists("\Gvozdb\Dumper\Database\\{$class}")) {
                throw new \Exception("Database handler `{$class}` not found.");
            }

            $database = new \ReflectionClass("\Gvozdb\Dumper\Database\\{$class}");
            $database = $database->newInstance(array_merge($config, [
                'key' => $this->config['key'],
                'dest' => $this->config['dest'],
            ]));
            /** @var Database\AbstractDatabase $database */
            if ($database->export() === true) {
                $this->dumper->log->info("Successfully database dump for `{$this->config['key']}` user.");
            }

            //
            if ($filepath = $database->getFile()) {
                $this->compress($filepath, $filepath);
                @unlink($filepath);
            }
            unset($database, $filepath, $class, $config);
        } catch (\Exception $e) {
            $this->dumper->log->error($e->getMessage());
        }
    }


    /**
     * @throws \Exception
     */
    protected function upload()
    {
        if (empty($this->dumper->storages) || empty($this->files)) {
            return;
        }

        //
        $storages = $this->dumper->storages;
        if (!is_array($storages)) {
            $storages = [$storages];
        }
        foreach ($this->config['storages'] as $class => $config) {
            if (!class_exists("\Gvozdb\Dumper\Storage\\{$class}")) {
                $this->dumper->log->error("Storage handler `{$class}` not found.");
                continue;
            }
            try {
                $storage = new \ReflectionClass("\Gvozdb\Dumper\Storage\\{$class}");
                $storageInstance = $storage->newInstance($config);
                if ($storageInstance->enabled()) {
                    $this->storages[$class] = $storages[$class] = $storageInstance;
                }
            } catch (\Exception $e) {
                $this->dumper->log->error("It was not possible to initialize the storage instance `{$class}` for `{$this->config['key']}` user. Message: " . $e->getMessage());
            }
        }
        unset($class, $config, $storage, $storageInstance);

        //
        foreach ($this->files as $filepath => &$status) {
            /** @var Storage\AbstractStorage $storageInstance */
            foreach ($storages as $storageClass => $storageInstance) {
                try {
                    if ($storageInstance->upload($filepath)) {
                        $status = true;
                        $this->dumper->log->info("Successfully uploaded file `{$filepath}` to the storage `{$storageClass}`.");
                    }
                } catch (\Exception $e) {
                    $this->dumper->log->error("Failed uploading file `{$filepath}` to the storage `{$storageClass}`. Message: " . $e->getMessage());
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
        //
        if (!empty($this->storages)) {
            /** @var Storage\AbstractStorage $storageInstance */
            foreach ($this->storages as $storageClass => $storageInstance) {
                try {
                    if ($storageInstance->clean() === true) {
                        $this->dumper->log->info("Successfully cleaning old backups in the storage `{$storageClass}` for `{$this->config['key']}` user.");
                    }
                } catch (\Exception $e) {
                    $this->dumper->log->error("Could not remove old backups in the storage `{$storageClass}` for `{$this->config['key']}` user. Message: " . $e->getMessage());
                }
            }
        }

        // Remove temp files
        if (!empty($this->files)) {
            foreach ($this->files as $filepath => $status) {
                if ($status === true) {
                    @unlink($filepath);
                }
            }
        }
    }


    /**
     * Clears the folder on the file system
     *
     * @param string $path
     * @param bool $remove_self
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