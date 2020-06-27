<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib\Models;

use Aniart\Main\Cron\Lib\Services\CrontabService;
use DateTime;
use Exception;

class TaskConfig
{
    private $taskName = '';
    private $taskTitle = '';
    private $className = false;
    private $taskNamesList = [];
    private $userId = 0;
    private $lowPriority = false;
    /** @var CrontabLine|bool $crontabLine */
    private $crontabLine = false;
    private $simpleTask = true;

    /**
     * @param string $taskName
     * @param $configValue
     * @return TaskConfig|bool
     */
    public static function build(string $taskName, $configValue)
    {
        $objConfig = new self;
        $objConfig->taskName = $taskName;
        $objConfig->taskTitle = $taskName;
        $objConfig->crontabLine = CrontabService::getCrontabLine($taskName);
        $result = false;
        if (!empty($configValue) && is_array($configValue)) {
            $taskTitle = $configValue['TITLE'] ?? null;
            $task = $configValue['TASK'] ?? null;
            $userId = $configValue['USER_ID'] ?? null;
            $objConfig->taskTitle = (!empty($taskTitle)) ? $taskTitle : $taskName;
            $objConfig->userId = (!empty($userId)) ? intval($userId) : 0;
            $objConfig->lowPriority = $configValue['LOW_PRIORITY'] === true;
            if (!empty($task)) {
                if (is_string($task)) {
                    $objConfig->simpleTask = true;
                    $objConfig->className = $task;
                    $result = $objConfig;
                }
                if (is_array($task)) {
                    $objConfig->simpleTask = false;
                    foreach ($task as $oneTaskName) {
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

    public function getSubTaskNamesList(): array
    {
        return $this->taskNamesList;
    }

    public function getUserId(): int
    {
        return intval($this->userId);
    }

    public function getTaskName(): string
    {
        return $this->taskName;
    }

    public function isTaskSimple(): bool
    {
        return $this->simpleTask;
    }

    public function isTaskSequence(): bool
    {
        return !$this->isTaskSimple();
    }

    /**
     * @return bool|DateTime
     * @throws Exception
     */
    public function getTimeNextRun()
    {
        $result = false;
        if ($this->crontabLine) {
            $result = CrontabService::getTimeNextRun($this->crontabLine);
        }
        return $result;
    }

    public function getTaskTitle(): string
    {
        return $this->taskTitle;
    }

    public function isLowPriority(): bool
    {
        return $this->lowPriority;
    }

}
