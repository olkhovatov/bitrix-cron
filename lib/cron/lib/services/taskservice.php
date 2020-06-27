<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib\Services;

use Aniart\Main\Cron\Config;
use Aniart\Main\Cron\Lib\Models\TaskConfig;

class TaskService
{

    private static $instanceObject = null;

    public static function getInstance(): self
    {
        if (is_null(self::$instanceObject)) {
            self::$instanceObject = new self();
        }
        return self::$instanceObject;
    }

    public function getConfig(string $taskName)
    {
        $taskConfig = null;
        if (array_key_exists($taskName, Config::TASK_LIST)) {
            $taskConfig = TaskConfig::build($taskName, Config::TASK_LIST[$taskName]);
        }
        return $taskConfig;
    }

}
