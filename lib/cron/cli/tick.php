<?php

use Aniart\Main\Cron\Lib\Tick;

if (strtolower(php_sapi_name()) != 'cli') {
    die('run only in console. Stop program!');
}

require_once 'init.php';
(new Tick())->tick();
