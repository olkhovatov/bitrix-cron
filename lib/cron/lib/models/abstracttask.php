<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib\Models;

use Aniart\Main\Cron\Lib\Interfaces\TaskInterface;

abstract class AbstractTask implements TaskInterface
{
    protected $name;
    protected $arguments;

    abstract public function run();

    public function __construct(string $taskName, array $arguments)
    {
        $this->name = $taskName;
        $this->arguments = $arguments;
        //TODO Сделать проверку, что в $arguments массив строк(string[])
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

}
