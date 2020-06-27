<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib\Repositories;

use Aniart\Main\Cron\Config;
use Aniart\Main\Cron\Lib\Interfaces\TaskInterface;
use Aniart\Main\Cron\Lib\Models\ExecuteLine;
use Aniart\Main\Cron\Lib\Models\TaskSequence;
use Aniart\Main\Cron\Lib\Services\CrontabService;
use Aniart\Main\Cron\Lib\Services\TaskService;
use Aniart\Main\Cron\Lib\Services\RunService;
use Aniart\Main\Cron\Lib\Tools;

class TaskRepository
{
    private static $instanceObject = null;

    public static function getInstance(): self
    {
        if (is_null(self::$instanceObject)) {
            self::$instanceObject = new self();
        }
        return self::$instanceObject;
    }

    /**
     * @param string $taskName
     * @param array $taskArguments
     * @return TaskInterface|null
     */
    public function getByName(string $taskName, array $taskArguments = [])
    {
        $result = null;
        $taskConfig = TaskService::getInstance()->getConfig($taskName);
        if ($taskConfig) {
            if ($taskConfig->isTaskSimple()) {
                $className = $taskConfig->getClassName();
                if (strlen($className) > 0 && class_exists($className)) {
                    $objTask = new $className($taskConfig->getTaskName(), $taskArguments);
                    if ($objTask instanceof TaskInterface) {
                        $result = $objTask;
                    }
                }
            } else {
                $result = new TaskSequence($taskName, $taskArguments);
            }
        }
        return $result;
    }

    public function getTaskListCrontabTimeStart(): array
    {
        $result = [];
        foreach (CrontabService::getTaskNamesTimeStart() as $taskName) {
            $taskArgs = CrontabService::getCrontabLine($taskName)->getExecuteLine()->getTaskArgs();
            $task = $this->getByName($taskName, $taskArgs);
            if ($task) {
                $result[] = $task;
            }
        }
        return $result;
    }

    public function getWaitRun(): array
    {
        $result = [];
        $dirWaitToRun = Tools::getDirVar() . RunService::DIR_WAIT_RUN_TRIGGER;
        foreach (glob("{$dirWaitToRun}/*.run") as $fileFullName) {
            $strExecuteLine = file_get_contents($fileFullName);
            $executeLine = new ExecuteLine($strExecuteLine);
            $task = $this->getByName($executeLine->getTaskName(), $executeLine->getTaskArgs());

            if ($task) {
                $result[] = $task;
            }
        }
        return $result;
    }

    public function getAll(): array
    {
        $result = [];
        foreach (array_keys(Config::TASK_LIST) as $taskName) {
            $task = $this->getByName($taskName);
            $result[] = $task;
        }
        return $result;
    }

}
