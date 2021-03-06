<?php
declare(strict_types=1);

namespace Aniart\Main\Cron;

use Aniart\Main\Cron\Lib\Models\ExecuteLine;
use Aniart\Main\Cron\Lib\Models\Status;
use Aniart\Main\Cron\Lib\Models\Progress;
use Aniart\Main\Cron\Lib\Repositories\ProgressRepository;
use Aniart\Main\Cron\Lib\Repositories\StatusRepository;
use Aniart\Main\Cron\Lib\Services\RunService;
use Exception;

class Manager
{
    /**
     * Отметить для запуска на очередном тике
     * Запустится в отдельном процессе
     * @param string $strExecute
     */
    public static function addToRun(string $strExecute)
    {
        $strExecute = trim($strExecute);
        RunService::getInstance()->addToRun(new ExecuteLine($strExecute));
    }

    /**
     * Попытка запустить задачу немедленно(для отладки)
     * Если по каким-то причинам запуск отложен/пропущен, то больше не будет попыток запуска.
     * Запустится в отдельном процессе
     * @param string $strExecute
     * @throws Exception
     */
    public static function runTaskNow(string $strExecute)
    {
        $strExecute = trim($strExecute);
        RunService::getInstance()->runTaskImmediately($strExecute);
    }

    public static function getProgress(string $taskName): Progress
    {
        return ProgressRepository::getInstance()->getByTaskName($taskName);
    }

    public static function getStatus(string $taskName): Status
    {
        return StatusRepository::getInstance()->getByTaskName($taskName);
    }

}
