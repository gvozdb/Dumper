<?php

namespace Gvozdb\Dumper\Storage;

abstract class AbstractStorage
{
    /**
     * @var array $config
     */
    protected $config = [];
    /**
     * @var string $message
     */
    public $message;

    /**
     * @param array $config
     *
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'path' => '',
        ], $config);

        $this->config['path'] = strftime(@$this->config['path'] ?: '');
    }

    /**
     * @return bool
     */
    public function enabled()
    {
        foreach (['path'] as $k) {
            if (empty($this->config[$k])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $filepath
     *
     * @return void|bool
     */
    abstract public function upload($filepath);

    /**
     * @return void|bool
     */
    abstract public function clean();

    /**
     * @return string
     */
    abstract protected function getPath();
}