<?php

use Aniart\Main\Cron\Manager;

if (strtolower(php_sapi_name()) != 'cli') {
    die('run only in console. Stop program!');
}

require_once '../cli/init.php';
require($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/include/prolog_before.php');

Manager::runTaskNow('Task1 arg1 arg2 arg3');
//Manager::addToRun('test');
