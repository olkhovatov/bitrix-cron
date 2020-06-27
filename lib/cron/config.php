<?php

namespace Aniart\Main\Cron;

class Config
{
    // taskName => [
    //     'TITLE' => '',
    //     'TASK' => Tasks\Example\Task1::class,
    //     'TASK' => ['taskName-1', 'taskName-2', ...]
    //     'USER_ID' => 0,
    //     'LOW_PRIORITY' => true
    // ],

    const TASK_LIST = [
        'Task1' => [
            'TITLE' => 'Задача-1',
            'TASK' => Tasks\Example\Task1::class,
        ],
        'Task2' => [
            'TITLE' => 'Задача-2',
            'TASK' => Tasks\Example\Task2::class,
        ],
        'Task3' => [
            'TITLE' => 'Задача-3',
            'TASK' => Tasks\Example\Task3::class,
        ],
        'TaskSeq' => [
            'TITLE' => 'Последовательность',
            'TASK' => ['Task2', 'Task3'],
        ],

    ];

    const CRONTAB = [
        '* * * * * Task1',
        '*/2 * * * * TaskSeq',
    ];

}
