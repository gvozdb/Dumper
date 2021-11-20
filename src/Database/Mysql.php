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
     * @return void|bool
     *
     * @throws \Exception
     */
    public function export()
    {
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
        $arguments = [
            'mysqldump',
            '--skip-lock-tables',
            '--no-tablespaces',
            '-h ' . $this->config['host'],
            '-u' . $this->config['user'],
            '-p"' . $this->config['pass'] . '"',
            $this->config['name'],
            '>',
            $dest,
            '2>/dev/null', // no warnings!
        ];

        //
        $command = join(' ', $arguments);
        $result = shell_exec($command);

        //
        if (!file_exists($dest)) {
            throw new \Exception("It was not possible to database dump `{$this->config['name']}`.");
        }
        $this->setFile($dest);

        return true;
    }
}