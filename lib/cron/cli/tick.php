<?php

use Aniart\Main\Cron\Lib\Tick;

if (strtolower(php_sapi_name()) != 'cli') {
    die('run only in console. Stop program!');
}

require_once 'init.php';
require($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/include/prolog_before.php');

$tick = new Tick();
$tick->tick();
