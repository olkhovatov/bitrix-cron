<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib;

use Aniart\Main\Cron\Lib\Interfaces\TaskInterface;
use Aniart\Main\Cron\Lib\Repositories\StatusRepository;
use Aniart\Main\Cron\Lib\Repositories\ProgressRepository;
use Aniart\Main\Cron\Lib\Services\TaskService;
use Aniart\Main\Cron\Lib\Services\RunService;
use Aniart\Main\Cron\Lib\Exceptions;
use Psr\Log\LoggerInterface;
use Throwable;

class Starter
{
    private $taskService;
    private $runService;
    private $logger;
    private $statusRepository;
    private $progressRepository;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->taskService = TaskService::getInstance();
        $this->runService = RunService::getInstance();
        $this->statusRepository = StatusRepository::getInstance();
        $this->progressRepository = ProgressRepository::getInstance();
        $this->logger = $logger;
    }

    public function runTask(TaskInterface $task)
    {
        global $USER;

        $taskName = $task->getName();
        cli_set_process_title($taskName);

        $taskConfig = TaskService::getInstance()->getConfig($taskName);
        $userId = $taskConfig->getUserId();
        if ($userId > 0) {
            if ($USER) {
                $authResult = $USER->Authorize($userId);
                if (!$authResult) {
                    $this->runService->removeRunTrigger($task);
                    $errMsg = "{$taskName}: Пропуск. Ошибка авторизации userId={$userId}";
                    $this->taskLog($errMsg);
                    return;
                }
            }
        }

        if ($this->runService->lockTask($task)) {
            $startTime = microtime(true);
            $status = $this->statusRepository->getNew($task->getName());
            $progress = $this->progressRepository->getNew($task->getName());
            $errMsg = '';
            try {
                $status->setStatusRun();
                $this->statusRepository->save($status);
                $progress->setMessage("{$taskName}: Старт");
                $this->progressRepository->save($progress);
                $this->taskLog("{$taskName}: Старт");
                // Удаляем триггер запуска непосредственно перед запуском.
                // Пока выполняется задача возможно установить новый триггер на запуск
                $this->runService->removeRunTrigger($task);
                $task->run();
                $status->setStatusCompleted();
                $this->statusRepository->save($status);
            } catch (Exceptions\SequenceLoopException $e) {
                $this->runService->removeRunTrigger($task);
                $errMsg = $e->getMessage();
                $status->setStatusErrorMessage($errMsg);
                $this->statusRepository->save($status);
            } catch (Exceptions\SequenceBusyException $e) {
                // тут файл-триггер не удаляем. Попробуем запустить на следующем тике.
                $status->setStatusWait();
                $this->statusRepository->save($status);
                $this->taskLog("{$taskName}: Запуск отложен");
            } catch (Throwable $e) {
                $this->runService->removeRunTrigger($task);
                $errMsg = "{$taskName}: " . strval($e);
            } finally {
                $this->runService->unlockTask($task);
                $memoryPeak = round(memory_get_peak_usage() / 1024 / 1024, 1); // Mb
                $stopTime = microtime(true);
                $time = round($stopTime - $startTime, 1); // c
                $status->setMicroTimeEnd(microtime(true));
                $status->setMemory($memoryPeak);
                $this->statusRepository->save($status);
                if (strlen($errMsg) > 0) {
                    $status->setStatusErrorMessage($errMsg);
                    $this->statusRepository->save($status);
                    $this->taskLog($errMsg);
                }
                $progress->setMessage('');
                $this->progressRepository->save($progress);
                $this->taskLog("{$taskName}: Стоп time:{$time}c, memory:{$memoryPeak}Mb");
            }
        } else {
            $this->taskLog("{$taskName}:  Выполняется. Запуск отложен.");
        }

    }

    private function taskLog($message)
    {
        if ($this->logger) {
            $this->logger->info($message);
        }
    }
}
