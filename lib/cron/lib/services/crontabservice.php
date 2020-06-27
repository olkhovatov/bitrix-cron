<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib\Services;

use Cron\CronExpression;
use Aniart\Main\Cron\Config;
use Aniart\Main\Cron\Lib\Models\CrontabLine;
use DateTime;
use Exception;

class CrontabService
{
    /**
     * @return string[]
     */
    public static function getTaskNamesTimeStart(): array
    {
        $result = [];
        foreach (self::getCrontabLines() as $crontabLine) {
            $strSchedule = $crontabLine->getStrSchedule();
            if (CronExpression::isValidExpression($strSchedule)) {
                $cron = CronExpression::factory($strSchedule);
                if ($cron->isDue()) {
                    $result[] = $crontabLine->getExecuteLine()->getTaskName();
                }
            } else {
                //$this->log('Ошибка в строке расписания запуска: ' . $crontabLine->getLine());
            }
        }
        return $result;
    }

    /**
     * @param string $taskName
     * @return CrontabLine|bool
     */
    public static function getCrontabLine(string $taskName)
    {
        $result = false;
        foreach (self::getCrontabLines() as $crontabLine) {
            if ($crontabLine->getExecuteLine()->getTaskName() == $taskName) {
                $result = $crontabLine;
                break;
            }
        }
        return $result;
    }

    /**
     * @param CrontabLine $crontabLine
     * @return bool|DateTime
     * @throws Exception
     */
    public static function getTimeNextRun(CrontabLine $crontabLine)
    {
        $result = false;
        $strSchedule = $crontabLine->getStrSchedule();
        if (CronExpression::isValidExpression($strSchedule)) {
            $cron = CronExpression::factory($strSchedule);
            $result = $cron->getNextRunDate();
        }
        return $result;
    }

    /** @return CrontabLine[] */
    private static function getCrontabLines(): array
    {
        $result = [];
        if (is_array(Config::CRONTAB)) {
            $result = array_map(function ($line) {
                return new CrontabLine(trim($line));
            }, Config::CRONTAB);
        }
        return $result;
    }
}
