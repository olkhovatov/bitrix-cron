<?php

namespace Aniart\Main\Cron\Lib\Exceptions;

use Exception;

class AccessControlException extends Exception
{
    const CANT_MAKE_DIR = 'Не возможно создать каталог ';
    const NON_WRITABLE_DIR = 'Нет прав на запись в каталог ';
    const IS_NOT_DIR = 'Не является каталогом ';
}
