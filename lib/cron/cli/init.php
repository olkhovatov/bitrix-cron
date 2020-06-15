<?php

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $pathParts = explode('/', dirname(__FILE__));
    array_pop($pathParts); // -> /local/modules/aniart.main/lib/cron
    array_pop($pathParts); // -> /local/modules/aniart.main/lib/
    array_pop($pathParts); // -> /local/modules/aniart.main/
    array_pop($pathParts); // -> /local/modules/
    array_pop($pathParts); // -> /local/
    array_pop($pathParts); // -> /
    $_SERVER['DOCUMENT_ROOT'] = implode('/', $pathParts);
}

define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
define('NO_KEEP_STATISTIC', true);
