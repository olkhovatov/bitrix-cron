<?php

namespace Aniart\Main\Cron\Lib\Services;

use Aniart\Main\Cron\Lib\Models\AbstractTask;
use DateTime;
use Exception;

class TaskStatusService
{
    const STATUS_NULL = 0;
    const STATUS_WAIT = 1;
    const STATUS_RUN = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_ERROR = 4;

    protected $taskName = '';
    protected $timeBegin = false;
    protected $timeDuration = false;
    protected $memoryPeak = false;
    protected $status = self::STATUS_NULL;
    protected $errorMessage = false;

    /**
     * TaskStatus constructor.
     * @param AbstractTask $task
     */
    public function __construct(AbstractTask $task)
    {
        $this->taskName = $task->getName();
    }

    public function clear()
    {
        $this->status = self::STATUS_NULL;
        $this->timeBegin = false;
        $this->timeDuration = false;
        $this->memoryPeak = false;
        $this->errorMessage= false;
        $this->save();
    }

    /**
     * @return DateTime|bool
     */
    public function getTimeBegin()
    {
        return $this->timeBegin;
    }

    /**
     * @return TaskStatusService
     * @throws Exception
     */
    public function initTimeBegin()
    {
        $this->timeBegin = (new DateTime())->setTimestamp(microtime(true));
        return $this->save();
    }

    /**
     * @return mixed
     */
    public function getTimeDuration()
    {
        return $this->timeDuration;
    }

    /**
     * @param mixed $timeDuration
     * @return TaskStatusService
     */
    public function setTimeDuration($timeDuration)
    {
        $this->timeDuration = $timeDuration;
        return $this->save();
    }

    /**
     * @return mixed
     */
    public function getMemoryPeak()
    {
        return $this->memoryPeak;
    }

    /**
     * @param mixed $memoryPeak
     * @return TaskStatusService
     */
    public function setMemoryPeak($memoryPeak)
    {
        $this->memoryPeak = $memoryPeak;
        return $this->save();
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     * @return TaskStatusService
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this->save();
    }

    /**
     * @return mixed
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @param mixed $errorMessage
     * @return TaskStatusService
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
        $this->status = self::STATUS_ERROR;
        return $this->save();
    }

    /**
     * @return string
     */
    public function getTaskName()
    {
        return $this->taskName;
    }

    /**
     * @return mixed|string
     */
    public function getStatusTxt()
    {
        $ar = [
            self::STATUS_NULL => '',
            self::STATUS_WAIT => 'Ожидает запуска',
            self::STATUS_RUN => 'Выполняется',
            self::STATUS_COMPLETED => 'Завершена',
            self::STATUS_ERROR => 'Ошибка',
        ];
        $statusTxt = '';
        if (isset($ar[$this->getStatus()])) {
            $statusTxt = $ar[$this->getStatus()];
        }
        return $statusTxt;
    }

    /**
     * @return $this
     */
    protected function save()
    {
        RunService::getInstance()->saveStatus($this);
        return $this;
    }

}
