<?php

namespace Aniart\Main\Cron\Lib\Services;

use Aniart\Main\Cron\Config;
use Aniart\Main\Cron\Lib\Models\ExecuteLine;
use Aniart\Main\Cron\Lib\Models\AbstractTask;
use Aniart\Main\Cron\Lib\Repositories\TaskRepository;
use Aniart\Main\Cron\Lib\Tools;
use Exception;

class RunService
{
    const DIR_WAIT_RUN_TRIGGER = '/wait_run';
    const DIR_LOCK = '/lock';
    const DIR_STATUS = '/status';

    protected static $instanceObject = null;
    /** @var  TaskRepository $taskRepository*/
    protected $taskRepository;
    protected $taskLockFileHandlers = [];

    protected function __construct()
    {
        $this->taskRepository = TaskRepository::getInstance();
    }

    /** @return $this */
    public static function getInstance()
    {
        if (is_null(self::$instanceObject)) {
            self::$instanceObject = new self();
        }
        return self::$instanceObject;
    }

    /**
     * Запустить задачу немедленно
     * Запустится в отдельном процессе
     * @param string $strExecute
     * @return $this
     * @throws Exception
     */
    public function runTaskImmediately(string $strExecute)
    {
        $executeLine = new ExecuteLine($strExecute);
        $task = $this->taskRepository->getByName($executeLine->getTaskName(), $executeLine->getTaskArgs());
        if ($task) {
            $this->runTask($task);
        }
        return $this;
    }

    /**
     * @param AbstractTask $task
     * @return $this
     * @throws Exception
     */
    public function runTask(AbstractTask $task)
    {
        $cmd = $this->buildRunCommand($task);
        if (function_exists('exec')) {
            exec($cmd);
        } else {
            throw new Exception('В PHP нет функции exec');
        }
        return $this;
    }

    /**
     * Отметить для запуска на очередном тике(будет создан файл-триггер)
     * Запустится в отдельном процессе
     * @param ExecuteLine $executeLine
     * @return $this
     */
    public function addToRun(ExecuteLine $executeLine)
    {
        $task = $this->taskRepository->getByName($executeLine->getTaskName(), $executeLine->getTaskArgs());
        if ($task) {
            $this->createRunTrigger($task);
        }
        return $this;
    }

    /**
     * Удалить файл-триггер, чтоб задача не запустилась на следующем тике
     * @param AbstractTask $task
     * @return $this
     */
    public function removeRunTrigger(AbstractTask $task)
    {
        $triggerFileName = $this->getTriggerFileName($task);
        if (file_exists($triggerFileName)) {
            unlink($triggerFileName);
        }
        return $this;
    }

    /**
     * Заблокировать задачу, запретить повторный запуск
     * @param AbstractTask $task
     * @return bool
     */
    public function lockTask(AbstractTask $task)
    {
        $result = false;
        $lockFileName = $this->getLockFileName($task);
        if (file_exists($lockFileName)) {
            $lockFileHandler = fopen($lockFileName, 'r+');
        } else {
            $lockFileHandler = fopen($lockFileName, 'w+');
        }

        if ($lockFileHandler) {
            if (flock($lockFileHandler, LOCK_EX | LOCK_NB)) {
                $this->taskLockFileHandlers[$task->getName()] = $lockFileHandler;
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Заблокировать все задачи последовательности
     * @param AbstractTask[] $taskSequence
     * @return bool
     */
    public function lockTasksSequence(array $taskSequence)
    {
        $lockError = false;
        $lockedTasks = [];
        foreach ($taskSequence as $task) {
            if (!array_key_exists($task->getName(), $lockedTasks)) {
                if ($this->lockTask($task)) {
                    $lockedTasks[$task->getName()] = $task;
                } else {
                    // не удалось заблокировать очередную задачу
                    // разблокировать задачи последовательности, которые успели заблокировать
                    $lockError = true;
                    foreach ($lockedTasks as $taskUnlock) {
                        $this->unlockTask($taskUnlock);
                    }
                    break;
                }
            }
        }
        return !$lockError;
    }

    /**
     * Разблокировать задачу, разрешить запуск(только для блокированных в этом процессе)
     * @param AbstractTask $task
     * @return $this
     */
    public function unlockTask(AbstractTask $task)
    {
        $taskName = $task->getName();
        $lockFileHandler = $this->taskLockFileHandlers[$taskName];
        if ($lockFileHandler) {
            flock($lockFileHandler, LOCK_UN);
        }
        $lockFileName = $this->getLockFileName($task);
        if (file_exists($lockFileName)) {
            unlink($lockFileName);
        }
        unset($this->taskLockFileHandlers[$taskName]);
        return $this;
    }

    /**
     * @param TaskStatusService $status
     * @return $this
     */
    public function saveStatus(TaskStatusService $status)
    {
        $taskName = $status->getTaskName();
        $task = $this->taskRepository->getByName($taskName);
        $statusFileName = $this->getTaskStatusFileName($task);
        file_put_contents($statusFileName, serialize($status));
        return $this;
    }

    /**
     * @param AbstractTask $task
     * @return TaskStatusService|bool
     */
    public function getTaskStatus(AbstractTask $task)
    {
        $statusFileName = $this->getTaskStatusFileName($task);
        $status = $this->getStatusFromFile($statusFileName);
        if(!$status){
            $status = new TaskStatusService($task);
        }
        return $status;
    }

    /**
     * @param AbstractTask $task
     * @return string
     */
    public function getTaskStatusFileName(AbstractTask $task)
    {
        $taskName = $task->getName();
        $fileName = Config::DIR_VAR . self::DIR_STATUS . "/{$taskName}.status";
        Tools::makeDirPath($fileName);
        return $fileName;
    }

    /**
     * @param AbstractTask $task
     * @return string
     */
    public function buildStrExecuteLine(AbstractTask $task)
    {
        return implode(' ', array_merge([$task->getName()], $task->getArguments()));
    }

    /**
     * @param AbstractTask $task
     * @return string
     */
    protected function buildRunCommand(AbstractTask $task)
    {
        $cmd = '';
        $cmd .= implode(' ',
            [
                Config::PHP,
                '-f',
                Config::STARTER_SCRIPT,
                $this->buildStrExecuteLine($task),
                '> /dev/null 2>&1 &'
            ]
        );
        $taskName = $task->getName();
        $taskConfig = TaskService::getInstance()->getConfig($taskName);
        if($taskConfig->isLowPriority()){
            $cmd = 'ionice -c3 nice -n 19 ' . $cmd;
        }
        return $cmd;
    }

    /**
     * @param AbstractTask $task
     */
    protected function createRunTrigger(AbstractTask $task)
    {
        $triggerFileName = $this->getTriggerFileName($task);
        $strExecuteLine = $this->buildStrExecuteLine($task);
        file_put_contents($triggerFileName, $strExecuteLine);
    }

    /**
     * @param AbstractTask $task
     * @return string
     */
    protected function getTriggerFileName(AbstractTask $task)
    {
        $taskName = $task->getName();
        $fileName = Config::DIR_VAR . self::DIR_WAIT_RUN_TRIGGER . "/{$taskName}.run";
        Tools::makeDirPath($fileName);
        return $fileName;
    }

    /**
     * @param AbstractTask $task
     * @return string
     */
    protected function getLockFileName(AbstractTask $task)
    {
        $taskName = $task->getName();
        $fileName = Config::DIR_VAR . self::DIR_LOCK . "/{$taskName}.lock";
        Tools::makeDirPath($fileName);
        return $fileName;
    }

    /**
     * @param string $fileName
     * @return TaskStatusService|bool
     */
    protected function getStatusFromFile(string $fileName)
    {
        $result = false;
        $status = unserialize(file_get_contents($fileName));
        if ($status instanceof TaskStatusService) {
            $result = $status;
        }
        return $result;
    }

}
