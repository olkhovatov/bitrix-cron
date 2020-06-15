<?php

namespace Aniart\Main\Cron\Lib\Models;

class ExecuteLine
{
    protected $taskName = '';
    protected $taskArgs = [];

    public function __construct(string $strExecute)
    {
        // "схлопнуть" пробелы
        $strExecute = preg_replace('/\s\s+/', ' ', $strExecute);
        $strExecute = trim($strExecute);

        $strExecuteParts = explode(' ', $strExecute);
        if(count($strExecuteParts) > 0){
            $this->taskName = current($strExecuteParts);
        }
        if(count($strExecuteParts) > 1){
            $this->taskArgs = array_slice($strExecuteParts, 1);
        }
    }

    /** @return string */
    public function getTaskName()
    {
        return $this->taskName;
    }

    /**
     * @return String[]
     */
    public function getTaskArgs()
    {
        return $this->taskArgs;
    }

}
