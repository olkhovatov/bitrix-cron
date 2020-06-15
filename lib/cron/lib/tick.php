<?php

namespace Aniart\Main\Cron\Lib;

use Aniart\Main\Cron\Lib\Models\ExecuteLine;
use Aniart\Main\Cron\Lib\Repositories\TaskRepository;
use Aniart\Main\Cron\Lib\Services\TaskService;
use Aniart\Main\Cron\Lib\Services\RunService;

class Tick
{
    protected $taskService;
    protected $runService;
    protected $taskRepository;

    public function __construct()
    {
        $this->taskService = TaskService::getInstance();
        $this->runService = RunService::getInstance();
        $this->taskRepository = TaskRepository::getInstance();
    }

    public function tick()
    {
        // список задач, для которых настало время запуска по cron-у
        $taskList = $this->taskRepository->getTaskListCrontabTimeStart();
        foreach ($taskList as $task) {
            $strExecuteLine = $this->runService->buildStrExecuteLine($task);
            $executeLine = new ExecuteLine($strExecuteLine);
            $this->runService->addToRun($executeLine);
        }

        // список ожидающих запуск
        $taskList = $this->taskRepository->getWaitRun();
        foreach ($taskList as $task) {
            $this->runService->runTask($task);
        }
    }

}
