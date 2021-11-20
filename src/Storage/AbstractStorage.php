<?php

namespace Gvozdb\Dumper\Storage;

use Gvozdb\Dumper\Backup;

abstract class AbstractStorage
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
     * @var string $message
     */
    public $message;


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
            'path' => '',
        ], $config);

        $this->config['path'] = strftime(@$this->config['path'] ?: '');

        $this->config['expires'] = is_array($this->config['expires'])
            ? $this->config['expires']
            : ['short_max_days' => $this->config['expires']];
        $this->config['expires']['short_max_days'] = @$this->config['expires']['short_max_days'] ?: 1;
        $this->config['expires'] = array_merge([
            'short_step' => 1,
            'long_max_days' => $this->config['expires']['short_max_days'],
            'long_step' => 1,
        ], $this->config['expires']);
        $this->config['expires']['long_max_days'] = $this->config['expires']['long_max_days'] < $this->config['expires']['short_max_days']
            ? $this->config['expires']['short_max_days']
            : $this->config['expires']['long_max_days'];
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