<?php

namespace Gvozdb\Dumper\Database;

class Mysql extends AbstractDatabase
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
     * @return mixed|void
     */
    public function export()
    {
        //
        foreach (['host', 'name', 'user', 'pass'] as $k) {
            if (empty($this->config[$k])) {
                return;
            }
        }

        //
        $dest = $this->config['dest'];
        if (!preg_match('/\.sql/i', $dest)) {
            $dest .= '.sql';
        }

        //
        $command = join(' ', [
            'mysqldump',
            '-h ' . $this->config['host'],
            '--skip-lock-tables',
            '-u' . $this->config['user'],
            '-p"' . $this->config['pass'] . '"',
            $this->config['name'],
            '>',
            $dest,
        ]);
        $output = shell_exec($command);

        //
        $this->setFilePath(file_exists($dest) ? $dest : null);
    }
}