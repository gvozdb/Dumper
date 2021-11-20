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
     * @param array $config
     */
    public function __construct($filepath, array $config = [])
    {
        $this->setDefault($config);

        if (file_exists($filepath)) {
            if ($tmp = Yaml::parse(file_get_contents($filepath))) {
                if (is_array($tmp)) {
                    $this->config = array_merge($this->config, $tmp);
                    $this->config = $this->processSeparatorProperties($this->config, true);
                }
            }
            unset($tmp);
        }
    }


    /**
     * @param array $data
     */
    public function setDefault(array $data)
    {
        $this->config = array_merge($data, $this->config);
        $this->config = $this->processSeparatorProperties($this->config, false);
    }


    /**
     * Processing properties in root with dot separator
     *
     * @param array $data
     * @param bool $force
     *
     * @return array
     */
    protected function processSeparatorProperties(array $data, $force = false)
    {
        foreach ($data as $key => $value) {
            if (strstr($key, '.')) {
                $node = &$data;
                $node_keys = explode('.', $key);
                foreach ($node_keys as $key_index => $node_key) {
                    if ($key_index + 1 !== count($node_keys)) {
                        $node = &$node[$node_key];
                    } else {
                        if ($force === true) {
                            $node[$node_key] = isset($value)
                                ? $value : $node[$node_key];
                        } else {
                            $node[$node_key] = isset($node[$node_key])
                                ? $node[$node_key] : $value;
                        }
                    }
                }
                unset($node, $data[$key]);
            }
        }

        return $data;
    }


    /**
     * @return array
     */
    public function toArray()
    {
        return $this->config;
    }

    // /**
    //  * @param string $root
    //  * @param mixed $default
    //  *
    //  * @return array
    //  */
    // public function getNode($root, $default = [])
    // {
    //     return isset($this->config[$root])
    //         ? $this->config[$root]
    //         : $default;
    // }
}