<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib\Models;

class CrontabLine
{
    private $line;
    private $strSchedule = '';
    private $executeLine;

    public function __construct(string $lineCrontab)
    {
        // заменить последовательность пробелов одним
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

    public function getStrSchedule(): string
    {
        return $this->strSchedule;
    }

    public function getLine(): string
    {
        return $this->line;
    }

    public function getExecuteLine(): ExecuteLine
    {
        return $this->executeLine;
    }

}
