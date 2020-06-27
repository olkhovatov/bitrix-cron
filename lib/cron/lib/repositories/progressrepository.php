<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib\Repositories;

use Aniart\Main\Cron\Lib\Models\Progress;
use Aniart\Main\Cron\Lib\Tools;
use Aniart\Main\Cron\Lib\Exceptions\AccessControlException;

class ProgressRepository
{
    const DIR_PROGRESS = 'progress';
    private static $instanceObject = null;

    public static function getInstance(): self
    {
        if (is_null(self::$instanceObject)) {
            self::$instanceObject = new self();
        }
        return self::$instanceObject;
    }

    /**
     * ProgressRepository constructor.
     * @throws AccessControlException
     */
    private function __construct()
    {
        Tools::makeDirPath($this->getDirProgress());
    }

    public function getNew(string $taskName)
    {
        return new Progress($taskName);
    }

    public function getByTaskName(string $taskName)
    {
        $fileName = $this->getProgressFileName($taskName);
        $fileContent = file_get_contents($fileName);
        if ($fileContent) {
            $status = unserialize($fileContent);
            if (!($status instanceof Progress)) {
                $status = new Progress($taskName);
            }
        } else {
            $status = new Progress($taskName);
        }
        return $status;
    }

    public function save(Progress $progress)
    {
        $taskName = $progress->getTaskName();
        $fileName = $this->getProgressFileName($taskName);
        file_put_contents($fileName, serialize($progress));
    }

    private function getProgressFileName(string $taskName): string
    {
        return $this->getDirProgress() . "/{$taskName}.progress";
    }

    private function getDirProgress()
    {
        return implode('/', [Tools::getDirVar(), self::DIR_PROGRESS]);
    }

}
