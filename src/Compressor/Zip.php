<?php

namespace Gvozdb\Dumper\Compressor;

class Zip extends AbstractCompressor
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
        parent::__construct($config);
    }

    /**
     * @return void|bool
     *
     * @throws \Exception
     */
    public function compress()
    {
        //
        $dest = $this->config['dest'];
        if (!preg_match('/\.zip$/i', $dest)) {
            $dest .= '.zip';
        }

        //
        $command = join(' ', [
            'zip',
            '-r',
            $dest,
            $this->config['src'],
            $this->excludePrepare(),
        ]);
        $result = shell_exec($command);

        //
        if (!file_exists($dest)) {
            throw new \Exception("It was not possible to pack the source `{$this->config['src']}`.");
        }
        $this->setFilePath($dest);

        return true;
    }

    /**
     * @return string
     */
    protected function excludePrepare()
    {
        $exclude = [];
        if (!empty($this->config['exclude'])) {
            foreach ($this->config['exclude'] as $v) {
                if (substr($v, 0, 1) == '/') {
                    $v = $this->config['src'] . $v;
                }
                $exclude[] = '-x ' . str_replace('*', '\*', $v);
            }
        }
        $exclude = trim(join(' ', $exclude));

        return $exclude;
    }
}