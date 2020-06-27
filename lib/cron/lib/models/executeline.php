<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib\Models;

class ExecuteLine
{
    private $taskName = '';
    private $taskArgs = [];

    public function __construct(string $strExecute)
    {
        // заменить последовательность пробелов одним
        $strExecute = preg_replace('/\s\s+/', ' ', $strExecute);
        $strExecute = trim($strExecute);

        $strExecuteParts = explode(' ', $strExecute);
        if (count($strExecuteParts) > 0) {
            $this->taskName = current($strExecuteParts);
        }
        if (count($strExecuteParts) > 1) {
            $this->taskArgs = array_slice($strExecuteParts, 1);
        }
    }

    public function getTaskName(): string
    {
        return $this->taskName;
    }

    /**
     * @return string[]
     */
    public function getTaskArgs(): array
    {
        return $this->taskArgs;
    }

}
