<?php

namespace core;

use Whoops;

class Loader
{
    public static function autoLoad($class)
    {
        $vendorAutoloadFile = BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (file_exists($vendorAutoloadFile)) {
            include $vendorAutoloadFile;

            if (DEVELOP_ENV) {
                $whoops = new Whoops\Run;
                $whoops->pushHandler(new Whoops\Handler\PrettyPageHandler);
                $whoops->register();
            }
        }

        $class = ltrim($class, '\\');
        $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class) . PHP_EXT;
        if (file_exists(SYSTEM_PATH . $class_path)) {
            include SYSTEM_PATH . $class_path;
            return true;
        }
        if (file_exists(APP_PATH . $class_path)) {
            include APP_PATH . $class_path;
            return true;
        }
    }

}