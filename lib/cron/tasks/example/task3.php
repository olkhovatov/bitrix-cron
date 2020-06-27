<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Tasks\Example;

use Aniart\Main\Cron\Lib\Repositories\ProgressRepository;
use Aniart\Main\Cron\Lib\Models\AbstractTask;

class Task3 extends AbstractTask
{
    private $progressRepository;

    public function __construct(string $taskName, array $arguments)
    {
        parent::__construct($taskName, $arguments);
        $this->progressRepository = ProgressRepository::getInstance();
    }

    public function run()
    {
        $progress = $this->progressRepository->getNew($this->getName());
        $progress->setMessage('taskRun begin');
        $this->progressRepository->save($progress);
        for ($i = 1; $i <= 6; $i++) {
            $strArguments = implode(' ', $this->getArguments());
            $progress->setMessage("args: {$strArguments}, i:{$i}");
            $this->progressRepository->save($progress);
            sleep(5);
        }
        $progress->setMessage('taskRun end');
        $this->progressRepository->save($progress);
    }

}
