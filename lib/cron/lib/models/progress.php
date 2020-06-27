<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib\Models;

class Progress
{
    private $taskName;
    private $message;

    public function __construct(string $taskName)
    {
        $this->taskName = $taskName;
        $this->message = '';
    }

    public function getTaskName(): string
    {
        return $this->taskName;
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

}
