<?php

namespace Aniart\Main\Cron;

use Aniart\Main\Cron\Lib\Models\ExecuteLine;
use Aniart\Main\Cron\Lib\Services\TaskService;
use Aniart\Main\Cron\Lib\Services\TaskStatusService;
use Aniart\Main\Cron\Lib\Services\RunService;
use Aniart\Main\Cron\Lib\Repositories\TaskRepository;

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
        //RunService::getInstance()->addToRun($strExecute);
        RunService::getInstance()->addToRun(new ExecuteLine($strExecute));
    }

    /**
     * Попытка запустить задачу немедленно(для отладки)
     * Если по каким-то причинам запуск отложен/пропущен, то больше не будет попыток запуска.
     * Запустится в отдельном процессе
     * @param string $strExecute
     */
    public static function runTaskNow(string $strExecute)
    {
        $strExecute = trim($strExecute);
        RunService::getInstance()->runTaskImmediately($strExecute);
    }

    /**
     * @param $taskName
     * @return string
     */
    public static function getProgress(string $taskName)
    {
        $result = '';
        $task = TaskRepository::getInstance()->getByName($taskName);
        if ($task) {
            $result = TaskService::getInstance()->getProgress($task);
        }
        return $result;
    }

    /**
     * @param string $taskName
     * @return TaskStatusService|bool
     */
    public static function getStatus(string $taskName)
    {
        $result = false;
        $task = TaskRepository::getInstance()->getByName($taskName);
        if ($task) {
            $result = RunService::getInstance()->getTaskStatus($task);
        }
        return $result;
    }

}
