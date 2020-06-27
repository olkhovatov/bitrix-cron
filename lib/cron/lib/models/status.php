<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib\Models;

use UnexpectedValueException;

class Status
{
    const STATUS_NULL = 0;
    const STATUS_WAIT = 1;
    const STATUS_RUN = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_ERROR = 4;

    const TITLES = [
        self::STATUS_NULL => '',
        self::STATUS_WAIT => 'Ожидает запуска',
        self::STATUS_RUN => 'Выполняется',
        self::STATUS_COMPLETED => 'Завершена',
        self::STATUS_ERROR => 'Ошибка',
    ];

    private $taskName;
    private $microTimeBegin;
    private $microTimeEnd;
    private $duration;
    private $memory;
    private $status;
    private $statusErrorMessage;

    public function __construct(string $taskName, float $microTimeBegin = null)
    {
        $this->taskName = $taskName;
        $this->duration = 0.0;
        $this->memory = 0.0;
        $this->status = self::STATUS_NULL;
        $this->statusErrorMessage = '';
        if (is_null($microTimeBegin)) {
            $this->microTimeBegin = microtime(true);
        }
        $this->microTimeEnd = $this->microTimeBegin;
    }

    public function getTaskName(): string
    {
        return $this->taskName;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function getMicroTimeBegin(): float
    {
        return $this->microTimeBegin;
    }

    public function getMicroTimeEnd(): float
    {
        return $this->microTimeEnd;
    }

    public function setMicroTimeEnd(float $microTimeEnd)
    {
        $this->microTimeEnd = $microTimeEnd;
        $this->duration = $this->microTimeEnd - $this->microTimeBegin;
    }

    public function getMemory(): float
    {
        return $this->memory;
    }

    public function setMemory(float $memory)
    {
        $this->memory = $memory;
    }

    public function getStatusErrorMessage(): string
    {
        return $this->statusErrorMessage;
    }

    public function setStatusErrorMessage(string $statusErrorMessage)
    {
        $this->statusErrorMessage = $statusErrorMessage;
        $this->setStatus(self::STATUS_ERROR);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status)
    {
        if (in_array($status, $this::getAllowedStatuses())) {
            $this->status = $status;
        } else {
            throw new UnexpectedValueException();
        }
    }

    public function setStatusNull()
    {
        $this->setStatus(self::STATUS_NULL);
    }

    public function setStatusWait()
    {
        $this->setStatus(self::STATUS_WAIT);
    }

    public function setStatusRun()
    {
        $this->setStatus(self::STATUS_RUN);
    }

    public function setStatusCompleted()
    {
        $this->setStatus(self::STATUS_COMPLETED);
    }

    public function getTitle()
    {
        $title = '';
        if (array_key_exists($this->status, $this::TITLES)) {
            $title = $this::TITLES[$this->status];
        }
        return $title;
    }

    /**
     * @return int[]
     */
    private static function getAllowedStatuses()
    {
        return [
            self::STATUS_NULL,
            self::STATUS_WAIT,
            self::STATUS_RUN,
            self::STATUS_COMPLETED,
            self::STATUS_ERROR,
        ];
    }
}
