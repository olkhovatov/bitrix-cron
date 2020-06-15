<?php

namespace Aniart\Main\Cron\Lib\Models;

use Aniart\Main\Cron\Lib\Services\CrontabService;
use DateTime;

class TaskConfig
{
    protected $taskName = '';
    protected $taskTitle = '';
    protected $className = false;
    protected $taskNamesList = [];
    protected $userId = 0;
    protected $lowPriority = false;
    /** @var CrontabLine|bool $crontabLine */
    protected $crontabLine = false;
    protected $simpleTask = true;

    /**
     * @param string $taskName
     * @param $configValue
     * @return TaskConfig|bool
     */
    public static function build(string $taskName, $configValue){
        $objConfig = new self;
        $objConfig->taskName = $taskName;
        $objConfig->taskTitle = $taskName;
        $objConfig->crontabLine = CrontabService::getCrontabLine($taskName);
        $result = false;
        if(!empty($configValue) && is_array($configValue)){
            $taskTitle = $configValue['TITLE']??null;
            $task = $configValue['TASK']??null;
            $userId = $configValue['USER_ID']??null;
            $objConfig->taskTitle = (!empty($taskTitle)) ? $taskTitle : $taskName;
            $objConfig->userId = (!empty($userId)) ? intval($userId) : 0;
            $objConfig->lowPriority = ($configValue['LOW_PRIORITY'] === true) ? true : false;
            if(!empty($task)){
                if(is_string($task)){
                    $objConfig->simpleTask = true;
                    $objConfig->className = $task;
                    $result = $objConfig;
                }
                if(is_array($task)){
                    $objConfig->simpleTask = false;
                    foreach($task as $oneTaskName){
                        $objConfig->taskNamesList[] = trim(strval($oneTaskName));
                    }
                    $result = $objConfig;
                }
            }
        }
        return $result;
    }

    /**
     * @return string|bool
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return array
     */
    public function getSubTaskNamesList()
    {
        return $this->taskNamesList;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return intval($this->userId);
    }

    /**
     * @return string
     */
    public function getTaskName()
    {
        return $this->taskName;
    }

    /**
     * @return bool
     */
    public function isTaskSimple()
    {
        return $this->simpleTask;
    }

    /**
     * @return bool
     */
    public function isTaskSequence()
    {
        return !$this->isTaskSimple();
    }

    /**
     * @return bool|DateTime
     */
    public function getTimeNextRun()
    {
        $result = false;
        if($this->crontabLine){
            $result = CrontabService::getTimeNextRun($this->crontabLine);
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getTaskTitle()
    {
        return $this->taskTitle;
    }

    /**
     * @return bool
     */
    public function isLowPriority()
    {
        return $this->lowPriority;
    }

}
