<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib\Repositories;

use Aniart\Main\Cron\Lib\Models\Status;
use Aniart\Main\Cron\Lib\Tools;
use Aniart\Main\Cron\Lib\Exceptions\AccessControlException;

class StatusRepository
{
    const DIR_STATUS = 'status';
    private static $instanceObject = null;

    public static function getInstance(): self
    {
        if (is_null(self::$instanceObject)) {
            self::$instanceObject = new self();
        }
        return self::$instanceObject;
    }

    /**
     * StatusRepository constructor.
     * @throws AccessControlException
     */
    private function __construct()
    {
        Tools::makeDirPath($this->getDirStatus());
    }

    public function getNew(string $taskName)
    {
        return new Status($taskName);
    }

    public function getByTaskName(string $taskName)
    {
        $fileName = $this->getStatusFileName($taskName);
        $fileContent = file_get_contents($fileName);
        if ($fileContent) {
            $status = unserialize($fileContent);
            if (!($status instanceof Status)) {
                $status = new Status($taskName);
            }
        } else {
            $status = new Status($taskName);
        }
        return $status;
    }

    public function save(Status $status)
    {
        $taskName = $status->getTaskName();
        $fileName = $this->getStatusFileName($taskName);
        file_put_contents($fileName, serialize($status));
    }

    private function getStatusFileName(string $taskName): string
    {
        return $this->getDirStatus() . "/{$taskName}.status";
    }

    private function getDirStatus()
    {
        return implode('/', [Tools::getDirVar(), self::DIR_STATUS]);
    }

}
