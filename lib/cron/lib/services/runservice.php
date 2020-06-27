<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib\Services;

use Aniart\Main\Cron\Lib\Interfaces\TaskInterface;
use Aniart\Main\Cron\Lib\Models\ExecuteLine;
use Aniart\Main\Cron\Lib\Repositories\TaskRepository;
use Aniart\Main\Cron\Lib\Tools;
use Aniart\Main\Cron\Lib\Exceptions\AccessControlException;
use Exception;

class RunService
{
    const DIR_WAIT_RUN_TRIGGER = '/wait_run';
    const DIR_LOCK = '/lock';

    private static $instanceObject = null;
    private $taskRepository;
    private $taskLockFileHandlers = [];

    /**
     * RunService constructor.
     * @throws AccessControlException
     */
    private function __construct()
    {
        $this->taskRepository = TaskRepository::getInstance();
        Tools::makeDirPath($this->getDirTrigger());
        Tools::makeDirPath($this->getDirLock());
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
     * @param TaskInterface $task
     * @return $this
     * @throws Exception
     */
    public function runTask(TaskInterface $task)
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
     * @param TaskInterface $task
     * @return $this
     */
    public function removeRunTrigger(TaskInterface $task)
    {
        $triggerFileName = $this->getTriggerFileName($task);
        if (file_exists($triggerFileName)) {
            unlink($triggerFileName);
        }
        return $this;
    }

    /**
     * Заблокировать задачу, запретить повторный запуск
     * @param TaskInterface $task
     * @return bool
     */
    public function lockTask(TaskInterface $task)
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
     * @param TaskInterface[] $taskSequence
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
     * @param TaskInterface $task
     */
    public function unlockTask(TaskInterface $task)
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
    }

    public function buildStrExecuteLine(TaskInterface $task): string
    {
        return implode(' ', array_merge([$task->getName()], $task->getArguments()));
    }

    private function buildRunCommand(TaskInterface $task): string
    {
        $cmd = implode(' ',
            [
                PHP_BINARY,
                '-f',
                Tools::getStarterScriptName(),
                $this->buildStrExecuteLine($task),
                '> /dev/null 2>&1 &'
            ]
        );
        $taskName = $task->getName();
        $taskConfig = TaskService::getInstance()->getConfig($taskName);
        if ($taskConfig->isLowPriority()) {
            $cmd = 'ionice -c3 nice -n 19 ' . $cmd;
        }
        return $cmd;
    }

    private function createRunTrigger(TaskInterface $task)
    {
        $triggerFileName = $this->getTriggerFileName($task);
        $strExecuteLine = $this->buildStrExecuteLine($task);
        file_put_contents($triggerFileName, $strExecuteLine);
    }

    private function getTriggerFileName(TaskInterface $task): string
    {
        $taskName = $task->getName();
        return $this->getDirTrigger() . "/{$taskName}.run";
    }

    private function getLockFileName(TaskInterface $task): string
    {
        $taskName = $task->getName();
        return $this->getDirLock() . "/{$taskName}.lock";
    }

    private function getDirTrigger()
    {
        return Tools::getDirVar() . self::DIR_WAIT_RUN_TRIGGER;
    }

    private function getDirLock()
    {
        return Tools::getDirVar() . self::DIR_LOCK;
    }

}
