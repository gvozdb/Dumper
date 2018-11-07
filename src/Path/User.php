<?php

namespace Gvozdb\Dumper\Path;

class User extends AbstractPath
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
}