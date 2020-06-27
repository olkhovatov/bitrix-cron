<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib\Models;

use Aniart\Main\Cron\Lib\Exceptions;
use Aniart\Main\Cron\Lib\Interfaces\TaskInterface;
use Aniart\Main\Cron\Lib\Repositories\TaskRepository;
use Aniart\Main\Cron\Lib\Services\RunService;
use Aniart\Main\Cron\Lib\Services\TaskService;
use Aniart\Main\Cron\Lib\Repositories\StatusRepository;
use Aniart\Main\Cron\Lib\Repositories\ProgressRepository;

class TaskSequence extends AbstractTask
{
    private $taskConfig;
    private $taskService;
    private $progressRepository;
    private $runService;
    private $statusRepository;

    public function __construct(string $taskName, array $arguments)
    {
        parent::__construct($taskName, $arguments);
        $this->runService = RunService::getInstance();
        $this->taskService = TaskService::getInstance();
        $this->progressRepository = ProgressRepository::getInstance();
        $this->statusRepository = StatusRepository::getInstance();
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
            $oneTaskStatus = $this->statusRepository->getNew($oneTask->getName());
            $this->statusRepository->save($oneTaskStatus);
        }

        $progressTaskSequence = $this->progressRepository->getNew($this->getName());
        foreach ($subTaskList as $oneTask) {
            $oneTaskName = $oneTask->getName();
            $progressOneTask = $this->progressRepository->getNew($oneTaskName);

            $progressTaskSequence->setMessage("{$oneTaskName} старт");
            $this->progressRepository->save($progressTaskSequence);

            $progressOneTask->setMessage("старт");
            $this->progressRepository->save($progressOneTask);

            $oneTaskStatus = $this->statusRepository->getNew($oneTask->getName());
            $this->statusRepository->save($oneTaskStatus);
            $oneTask->run();
            $oneTaskStatus->setStatusCompleted();
            $oneTaskStatus->setMicroTimeEnd(microtime(true));
            $this->statusRepository->save($oneTaskStatus);

            $progressTaskSequence->setMessage("{$oneTaskName} стоп");
            $this->progressRepository->save($progressTaskSequence);

            $progressOneTask->setMessage('');
            $this->progressRepository->save($progressOneTask);
        }
    }

    /**
     * @return TaskInterface[]
     * @throws Exceptions\SequenceLoopException
     */
    public function getSubTaskList()
    {
        static $taskNamesStack = [];

        $taskList = [];
        $taskName = $this->getName();
        array_push($taskNamesStack, $taskName);
        foreach ($this->taskConfig->getSubTaskNamesList() as $oneTaskName) {
            if (in_array($oneTaskName, $taskNamesStack)) {
                $errorMsg = 'Петля: ' . implode(' ,', $taskNamesStack) . $oneTaskName;
                $taskNamesStack = [];
                throw new Exceptions\SequenceLoopException($errorMsg);
            }
            $oneTask = TaskRepository::getInstance()->getByName($oneTaskName);
            if ($oneTask) {
                $oneTaskConfig = TaskService::getInstance()->getConfig($oneTaskName);
                if ($oneTaskConfig->isTaskSequence()) {
                    $subTasksList = $oneTask->getSubTaskList();
                    $taskList = array_merge($taskList, $subTasksList);
                } else {
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

}
