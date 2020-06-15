<?php

namespace Aniart\Main\Cron\Lib;

use Aniart\Main\Cron\Config;
use Aniart\Main\Cron\Lib\Models\AbstractTask;
use Aniart\Main\Cron\Lib\Services\TaskStatusService;
use Aniart\Main\Cron\Lib\Services\TaskService;
use Aniart\Main\Cron\Lib\Services\RunService;
use Aniart\Main\Cron\Lib\Exceptions;
use Psr\Log\LoggerInterface;
use CUser;
use Throwable;

class Starter
{
    /** @var  TaskService $taskService */
    protected $taskService;
    /** @var  RunService $taskService */
    protected $runService;
    /** @var  LoggerInterface $logger */
    protected $logger = null;

    public function __construct()
    {
        $this->taskService = TaskService::getInstance();
        $this->runService = RunService::getInstance();
        $this->logger = new CronLogger(Config::DIR_LOG . '/starter.log');
    }

    public function taskLog($message)
    {
        if($this->logger){
            $this->logger->info($message);
        }
    }

    /**
     * @param AbstractTask $task
     */
    public function runTask(AbstractTask $task)
    {
        /** @var CUser $USER */
        global $USER;

        $taskName = $task->getName();
        cli_set_process_title($taskName);

        $taskConfig = TaskService::getInstance()->getConfig($taskName);
        $userId = $taskConfig->getUserId();
        if ($userId > 0) {
            if ($USER) {
                $authResult = $USER->Authorize($userId);
                if(!$authResult){
                    $this->runService->removeRunTrigger($task);
                    $errMsg = "{$taskName}: Пропуск. Ошибка авторизации userId={$userId}";
                    $this->taskLog($errMsg);
                    return;
                }
            }
        }

        if ($this->runService->lockTask($task)) {
            $startTime = microtime(true);
            $taskStatus = new TaskStatusService($task);

            $errMsg = '';
            try {
                $taskStatus
                    ->setStatus(TaskStatusService::STATUS_RUN)
                    ->initTimeBegin();
                $this->taskService->setProgress($task, "{$taskName}: Старт");
                $this->taskLog("{$taskName}: Старт");
                // Удаляем триггер запуска непосредственно перед запуском.
                // Пока выполняется задача возможно установить новый триггер на запуск
                $this->runService->removeRunTrigger($task);
                $task->run();
                $taskStatus->setStatus(TaskStatusService::STATUS_COMPLETED);
            } catch (Exceptions\SequenceLoopException $e) {
                $this->runService->removeRunTrigger($task);
                $errMsg = $e->getMessage();
                $taskStatus->setErrorMessage($errMsg);
            } catch (Exceptions\SequenceBusyException $e) {
                // тут файл-триггер не удаляем. Попробуем запустить на следующем тике.
                $taskStatus->setStatus(TaskStatusService::STATUS_WAIT);
                $this->taskLog("{$taskName}: Запуск отложен");
            } catch (Throwable $e) {
                $this->runService->removeRunTrigger($task);
                $errMsg = "{$taskName}: " . strval($e);
            } finally {
                $this->runService->unlockTask($task);
                $stopTime = microtime(true);
                $time = round($stopTime - $startTime, 1); // c
                $memoryPeak = round(memory_get_peak_usage() / 1024 / 1024, 1); // Mb
                $taskStatus
                    ->setTimeDuration($time)
                    ->setMemoryPeak($memoryPeak);
                if (strlen($errMsg) > 0) {
                    $taskStatus->setErrorMessage($errMsg);
                    $this->taskLog($errMsg);
                }
                $this->taskService->setProgress($task, '');
                $this->taskLog("{$taskName}: Стоп time:{$time}c, memory:{$memoryPeak}Mb");
            }
        } else {
            $this->taskLog("{$taskName}:  Выполняется. Запуск отложен.");
        }

    }

}
