<?php

namespace Aniart\Main\Cron\Tasks\Example;

use Aniart\Main\Cron\Lib\Models\AbstractTask;

class Task1 extends AbstractTask
{
    const TASK_NAME_ID =  'Task1';

    public function run()
    {
        // вся полезная работа
    }
}
