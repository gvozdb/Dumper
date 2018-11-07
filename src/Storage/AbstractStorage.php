<?php

namespace Gvozdb\Dumper\Storage;

abstract class AbstractStorage
{
    /**
     * @var array $config
     */
    protected $config = [];

    /**
     * @param array $config
     *
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'path' => null,
        ], $config);

        //
        foreach (['path'] as $k) {
            if (empty($this->config[$k])) {
                throw new \Exception('Storage config bad.');
            }
        }
        $this->config['path'] = strftime($this->config['path']);
    }

    /**
     * @param $filepath
     *
     * @return bool
     */
    abstract public function upload($filepath);

    /**
     * @return mixed
     */
    abstract public function clean();

    /**
     * @return string
     */
    abstract protected function getPath();
}