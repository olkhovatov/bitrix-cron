<?php

use Aniart\Main\Cron\Config;
use Aniart\Main\Cron\Lib\Starter;
use Aniart\Main\Cron\Lib\Repositories\TaskRepository;
use Aniart\Main\Cron\Lib\CronLogger;

if (strtolower(php_sapi_name()) != 'cli') {
    die('run only in console. Stop program!');
}

require_once 'init.php';
require($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/include/prolog_before.php');

$taskName = $argv[1];
$taskArgs = [];
if (count($argv) > 2) {
    $taskArgs = array_slice($argv, 2);
}
$task = TaskRepository::getInstance()->getByName($taskName, $taskArgs);
if ($task) {
    $logFileName = Config::DIR_LOG . '/' . 'starter.log';
    $starterLogger = new CronLogger($logFileName);
    $starter = new Starter();
    $starter->runTask($task);
}
