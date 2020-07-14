<?php

namespace Gvozdb\Dumper\Path;

class Log extends System
{
    /**
     *
     */
    protected function clean()
    {
        parent::clean();

        if (!empty($this->config['clean_logs']) && !empty($this->config['src'])) {
            $path = $this->config['src'];
            if (file_exists($path)) {
                $output = shell_exec("find {$path} -type f \( -name \"*.gz\" -o -name \"*.1*\" \) -exec rm '{}' \;");
            }
            unset($path);
        }
    }
}