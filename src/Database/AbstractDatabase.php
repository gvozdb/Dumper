<?php

namespace Gvozdb\Dumper\Database;

abstract class AbstractDatabase
{
    /**
     * @var array $config
     */
    protected $config = [];
    /**
     * @var string $filepath
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
                throw new \Exception('Database config bad.');
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

    abstract public function export();
}