<?php

namespace Aniart\Main\Cron\Lib\Models;

class CrontabLine
{
    protected $line;
    protected $strSchedule = '';
    protected $executeLine = null;

    public function __construct(string $lineCrontab)
    {
        // "схлопнуть" пробелы
        $lineCrontab = preg_replace('/\s\s+/', ' ', $lineCrontab);
        $this->line = trim($lineCrontab);

        $lineParts = explode(' ', $this->line);
        $strExecuteLine = '';
        if (count($lineParts) > 5) {
            $scheduleParts = array_slice($lineParts, 0, 5);
            $this->strSchedule = implode(' ', $scheduleParts);

            $executeLineParts = array_slice($lineParts, 5);
            $strExecuteLine = implode(' ', $executeLineParts);
        }
        $this->executeLine = new ExecuteLine($strExecuteLine);
    }

    /** @return string */
    public function getStrSchedule()
    {
        return $this->strSchedule;
    }

    /** @return string */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @return ExecuteLine|null
     */
    public function getExecuteLine()
    {
        return $this->executeLine;
    }

}
