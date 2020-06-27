<?php

use Aniart\Main\Cron\Lib\Starter;
use Aniart\Main\Cron\Lib\Repositories\TaskRepository;
use Aniart\Main\Cron\Lib\CronLogger;
use Aniart\Main\Cron\Lib\Tools;

if (strtolower(php_sapi_name()) != 'cli') {
    die('run only in console. Stop program!');
}

require_once 'init.php';

$taskName = $argv[1];
$taskArgs = [];
if (count($argv) > 2) {
    $taskArgs = array_slice($argv, 2);
}
$task = TaskRepository::getInstance()->getByName($taskName, $taskArgs);
if ($task) {
    $logFileName = implode('/', [Tools::getDirLog(), 'starter.log']);
    $starterLogger = new CronLogger($logFileName);
    (new Starter($starterLogger))->runTask($task);
}
