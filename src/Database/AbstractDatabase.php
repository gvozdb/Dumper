<?php

namespace Gvozdb\Dumper\Database;

abstract class AbstractDatabase
{
    /**
     * @var array $config
     */
    protected $config = [];
    /**
     * @var null|string $filepath
     */
    protected $filepath;

    /**
     * @param array $config
     *
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'dest' => null,
        ], $config);

        //
        foreach (['dest'] as $k) {
            if (empty($this->config[$k])) {
                throw new \Exception("Incorrect database config for `{$this->config['key']}`. " . print_r($this->config, 1));
            }
        }
    }

    /**
     *
     */
    public function getFilePath()
    {
        return $this->filepath;
    }

    /**
     * @param string $filepath
     */
    protected function setFilePath($filepath)
    {
        $this->filepath = $filepath;
    }

    /**
     * @return void|bool
     */
    abstract public function export();
}