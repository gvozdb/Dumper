<?php

namespace Gvozdb\Dumper\Compressor;

abstract class AbstractCompressor
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
            'src' => null,
            'dest' => null,
        ], $config);

        //
        foreach (['src', 'dest'] as $k) {
            if (empty($this->config[$k])) {
                throw new \Exception('Compressor config bad.');
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

    abstract public function compress();

    abstract protected function excludePrepare();
}