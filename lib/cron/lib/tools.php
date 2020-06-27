<?php
declare(strict_types=1);

namespace Aniart\Main\Cron\Lib;

use Aniart\Main\Cron\Lib\Exceptions\AccessControlException;
use COption;
use Exception;

class Tools
{

    /**
     * @param string $dir
     * @throws AccessControlException
     */
    public static function makeDirPath(string $dir)
    {
        if (!file_exists($dir)) {
            $oldUmask = umask(0);
            if (!mkdir($dir, 0750, true)) {
                $errMessage = AccessControlException::CANT_MAKE_DIR . $dir;
                throw new AccessControlException($errMessage);
            }
            mkdir($dir, 0750, true);
            umask($oldUmask);
        }

        if (!is_dir($dir)) {
            $errMessage = AccessControlException::IS_NOT_DIR . $dir;
            throw new AccessControlException($errMessage);
        }

        if (!is_writable($dir)) {
            $errMessage = AccessControlException::NON_WRITABLE_DIR . $dir;
            throw new AccessControlException($errMessage);
        }
    }

    public static function getDirLog()
    {
        return implode('/', [self::getDocumentRoot(), 'local/logs/cron']);
    }

    public static function getDirVar()
    {
        return implode('/', [self::getDocumentRoot(), self::getDirUpload(), 'cron_var']);
    }

    public static function getStarterScriptName(){
        return implode('/', [self::getDocumentRoot(), 'local/modules/aniart.main/lib/cron/cli/starter.php']);
    }

    private static function getDocumentRoot()
    {
        if (empty($_SERVER['DOCUMENT_ROOT'])) {
            throw new Exception('DOCUMENT_ROOT is empty');
        }
        return $_SERVER['DOCUMENT_ROOT'];
    }

    private static function getDirUpload()
    {
        return COption::GetOptionString("main", "upload_dir", "upload");
    }
}
