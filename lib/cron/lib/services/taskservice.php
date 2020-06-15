<?php

namespace Aniart\Main\Cron\Lib\Services;

use Aniart\Main\Cron\Config;
use Aniart\Main\Cron\Lib\Repositories\TaskRepository;
use Aniart\Main\Cron\Lib\Models\TaskConfig;
use Aniart\Main\Cron\Lib\Models\AbstractTask;
use Aniart\Main\Cron\Lib\Tools;

class TaskService
{

    const DIR_PROGRESS_MESSAGES = '/progress';

    protected static $instanceObject = null;
    /** @var  TaskRepository $taskRepository*/
    protected $taskRepository;

    protected function __construct()
    {
        $this->taskRepository = TaskRepository::getInstance();
    }

    /**
     * @return $this
     */
    public static function getInstance()
    {
        if (is_null(self::$instanceObject)) {
            self::$instanceObject = new self();
        }
        return self::$instanceObject;
    }

    /**
     * @param AbstractTask $task
     * @param string $msg
     * @return $this
     */
    public function setProgress(AbstractTask $task, string $msg)
    {
        $fileName = $this->getTaskProgressFileName($task);
        file_put_contents($fileName, $msg);
        return $this;
    }

    /**
     * @param AbstractTask $task
     * @return string
     */
    public function getProgress(AbstractTask $task)
    {
        $result = '';
        $fileName = $this->getTaskProgressFileName($task);
        if ($msg = file_get_contents($fileName)) {
            $result = trim($msg);
        }
        return $result;
    }

    /**
     * @param AbstractTask $task
     * @return string
     */
    protected function getTaskProgressFileName(AbstractTask $task)
    {
        $taskName = $task->getName();
        $fileName = Config::DIR_VAR . self::DIR_PROGRESS_MESSAGES . "/{$taskName}.txt";
        Tools::makeDirPath($fileName);
        return $fileName;
    }

    /**
     * @param string $taskName
     * @return TaskConfig|bool
     */
    public function getConfig(string $taskName)
    {
        $taskConfig = false;
        if(array_key_exists($taskName, Config::TASK_LIST)){
            $taskConfig = TaskConfig::build($taskName, Config::TASK_LIST[$taskName]);
        }
        return $taskConfig;
    }

}
