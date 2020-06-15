<?php

namespace Aniart\Main\Cron\Lib;

class Tools
{
    /**
     * @param string $fileName
     */
    public static function makeDirPath(string $fileName)
    {
        $dir = dirname($fileName);
        if (!file_exists($dir)) {
            $oldUmask = umask(0);
            mkdir($dir, 0750, true);
            umask($oldUmask);
        }
    }
}
