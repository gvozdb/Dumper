<?php

namespace Gvozdb\Dumper\Config;

use Symfony\Component\Yaml\Yaml;

class Load
{
    /**
     * @var array $config
     */
    protected $config = [];

    /**
     * @param string $filepath
     * @param array  $config
     */
    public function __construct($filepath, array $config = [])
    {
        $this->config = $config;

        if (file_exists($filepath)) {
            if ($tmp = Yaml::parse(file_get_contents($filepath))) {
                if (is_array($tmp)) {
                    $this->config = array_merge($this->config, $tmp);
                }
            }
            unset($tmp);
        }
    }

    /**
     * @param string $root
     * @param mixed  $default
     *
     * @return array
     */
    public function getNode($root, $default = [])
    {
        return isset($this->config[$root]) ? $this->config[$root] : $default;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->config;
    }
}