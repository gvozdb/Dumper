<?php

namespace Gvozdb\Dumper\Compressor;

abstract class AbstractCompressor
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
            'src' => null,
            'dest' => null,
            'compress' => 7,
            'split' => 0,
            'password' => '',
        ], $config);

        //
        foreach (['src', 'dest'] as $k) {
            if (empty($this->config[$k])) {
                throw new \Exception("Incorrect compressor config for `{$this->config['key']}`. " . print_r($this->config, 1));
            }
        }
    }


    /**
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }


    /**
     * @param array $files
     */
    protected function setFiles(array $files)
    {
        $this->files = $files;
    }


    /**
     * @return void|bool
     */
    abstract public function compress();
}