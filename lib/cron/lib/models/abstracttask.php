<?php

namespace Aniart\Main\Cron\Lib\Models;

abstract class AbstractTask
{
    protected $name;
    protected $arguments;

    abstract public function run();

    public function __construct(string $taskName, array $arguments)
    {
        $this->name = $taskName;
        $this->arguments = $arguments;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

}
