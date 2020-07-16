<?php

namespace Gvozdb\Dumper\Compressor;

abstract class AbstractCompressor
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
            'password' => '',
            'src' => null,
            'dest' => null,
        ], $config);

        //
        foreach (['src', 'dest'] as $k) {
            if (empty($this->config[$k])) {
                throw new \Exception("Incorrect compressor config for `{$this->config['key']}`. " . print_r($this->config, 1));
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
    abstract public function compress();

    /**
     * @return string
     */
    abstract protected function excludePrepare();
}