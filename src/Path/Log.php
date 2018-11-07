<?php

namespace Gvozdb\Dumper\Path;

class Log extends System
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
     *
     */
    protected function clean()
    {
        parent::clean();

        // Чистим логи
        if (!empty($this->config['clean_logs']) && !empty($this->config['src'])) {
            $path = $this->config['src'];
            if (file_exists($path)) {
                $output = shell_exec("find {$path} -type f \( -name \"*.gz\" -o -name \"*.1*\" \) -exec rm '{}' \;");
                // print_r($output);
            }
            unset($path);
        }
    }
}