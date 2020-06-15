<?php

namespace Aniart\Main\Cron\Lib\Models;

use Aniart\Main\Cron\Lib\Exceptions;
use Aniart\Main\Cron\Lib\Repositories\TaskRepository;
use Aniart\Main\Cron\Lib\Services\RunService;
use Aniart\Main\Cron\Lib\Services\TaskService;
use Aniart\Main\Cron\Lib\Services\TaskStatusService;

class TaskSequence extends AbstractTask
{
    protected $taskConfig;
    protected $taskService;
    protected $runService;
    public function __construct(string $taskName, array $arguments)
    {
        parent::__construct($taskName, $arguments);
        $this->runService = RunService::getInstance();
        $this->taskService = TaskService::getInstance();
        $this->taskConfig = $this->taskService->getConfig($taskName);
    }

    /**
     * @throws Exceptions\SequenceBusyException
     * @throws Exceptions\SequenceLoopException
     */
    public function run()
    {
        $subTaskList = $this->getSubTaskList();
        if (!$this->runService->lockTasksSequence($subTaskList)) {
            $errorMsg = 'Запуск отложен.';
            throw new Exceptions\SequenceBusyException($errorMsg);
        }

        foreach ($subTaskList as $oneTask) {
            $this->runService->getTaskStatus($oneTask)->clear();
        }
        foreach ($subTaskList as $oneTask) {
            $oneTaskName = $oneTask->getName();
            $this->taskService->setProgress($this, "{$oneTaskName} старт");
            $this->taskService->setProgress($oneTask, "старт");
            $oneTaskStatus = $this->runService->getTaskStatus($oneTask);
            $oneTaskStartTime = microtime(true);
            $oneTaskStatus
                ->setStatus(TaskStatusService::STATUS_RUN)
                ->initTimeBegin();
            $oneTask->run();
            $oneTaskStopTime = microtime(true);
            $time = round($oneTaskStopTime - $oneTaskStartTime, 1); // c
            $oneTaskStatus
                ->setStatus(TaskStatusService::STATUS_COMPLETED)
                ->setTimeDuration($time);

            $this->taskService->setProgress($this, "{$oneTaskName} стоп");
            $this->taskService->setProgress($oneTask, '');
        }
    }

    /**
     * @return AbstractTask[]|array
     * @throws Exceptions\SequenceLoopException
     */
    public function getSubTaskList()
    {
        static $taskNamesStack = [];

        $taskList = [];
        $taskName = $this->getName();
        array_push($taskNamesStack, $taskName);
        foreach ($this->taskGetConfig()->getSubTaskNamesList() as $oneTaskName) {
            if (in_array($oneTaskName, $taskNamesStack)) {
                $errorMsg = 'Петля: ' . implode(' ,', $taskNamesStack) . $oneTaskName;
                $taskNamesStack = [];
                throw new Exceptions\SequenceLoopException($errorMsg);
            }
            $oneTask = TaskRepository::getInstance()->getByName($oneTaskName);
            if ($oneTask) {
                $oneTaskConfig = TaskService::getInstance()->getConfig($oneTaskName);
                if($oneTaskConfig->isTaskSequence()){
                    $subTasksList = $oneTask->getSubTaskList();
                    $taskList = array_merge($taskList, $subTasksList);
                }else{
                    $taskList[] = $oneTask;
                }
            } else {
                // "Задача {} не найдена. Пропуск. "
                // это нужно проверять перед стартом последовательности
            }
        }
        array_pop($taskNamesStack);
        return $taskList;
    }

    /**
     * @return TaskConfig
     */
    public function taskGetConfig()
    {
        return $this->taskConfig;
    }
}
