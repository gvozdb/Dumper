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
        if (!preg_match('~\.zip$~ui', $dest)) {
            $dest .= '.zip';
        }
        $dest_pathinfo = pathinfo($dest);
        $dest_files = [];

        //
        $arguments = ['zip'];
        if (!empty($this->config['password'])) {
            $arguments[] = '--password="' . $this->config['password'] . '"';
        }
        if (!empty($this->config['compress'])) {
            $arguments[] = '-' . $this->config['compress'];
        }
        $arguments[] = '-r';
        $arguments[] = $dest;
        $arguments[] = $this->config['src'];
        $arguments[] = $this->getExcludeArgs();
        $arguments[] = '2>&1';

        //
        $command = join(' ', $arguments);
        shell_exec($command);
        unset($arguments, $command);

        if (!empty($this->config['split'])) {
            $arguments = [
                'split',
                '"' . $dest . '"',
                '-b ' . $this->config['split'],
                '-d',
                '"' . $dest . '."',
                // '2>&1',
            ];
            $command = join(' ', $arguments);
            shell_exec($command);
            unset($arguments, $command);

            foreach (scandir($dest_pathinfo['dirname']) as $k) {
                if (in_array($k, ['.', '..'], true)) {
                    continue;
                }
                if (preg_match('~^' . preg_quote($dest_pathinfo['basename'], '~') . '\.[0-9]+~u', $k)) {
                    $dest_files[] = $dest_pathinfo['dirname'] . '/' . $k;
                }
            }
        }
        if (empty($dest_files)) {
            $dest_files[0] = $dest;
        } elseif (count($dest_files) === 1 && md5_file($dest_files[0]) === md5_file($dest)) {
            @unlink($dest_files[0]);
            $dest_files[0] = $dest;
        } else {
            @unlink($dest);
        }

        if (!file_exists($dest_files[0])) {
            throw new \Exception("It was not possible to pack the source `{$this->config['src']}`.");
        }
        $this->setFiles($dest_files);

        return true;
    }


    /**
     * @return string
     */
    protected function getExcludeArgs()
    {
        $exclude = [];
        if (!empty($this->config['exclude'])) {
            foreach ($this->config['exclude'] as $v) {
                if (substr($v, 0, 1) === '/') {
                    $v = $this->config['src'] . $v;
                }
                $exclude[] = '-x ' . str_replace('*', '\*', $v);
            }
        }
        $exclude = trim(join(' ', $exclude));

        return $exclude;
    }
}