<?php

namespace Aniart\Main\Cron;

class Config
{
    // taskName => [
    //     'TITLE' => '',
    //     'TASK' => Tasks\Example\Task1::class,
    //     'TASK' => ['taskName-1', 'taskName-2', ...]
    //     'USER_ID' => 1,
    //     'LOW_PRIORITY' => true,
    // ],

    const TASK_LIST = [
        Tasks\Example\Task1::TASK_NAME_ID => [
            'TITLE' => 'Example',
            'TASK1' => Tasks\Example\Task1::class,
        ],

    ];

    const CRONTAB = [
        '*/10 * * * * TASK1',
    ];

    //const PHP = '/usr/bin/php';
    const PHP = 'php';
    const DIR_VAR = __DIR__ . '/../../../../../upload/cron_var';
    const DIR_LOG = __DIR__ . '/../../../../logs/cron';
    const STARTER_SCRIPT = __DIR__ . '/cli/starter.php';

}
