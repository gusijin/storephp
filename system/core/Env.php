<?php

namespace core;

class Env
{
    private static $env = null;
    private static $envFile = 'env';

    public static function reload()
    {
        $envFile = BASE_PATH . self::$envFile;
        if (is_file($envFile)) {
            self::$env = parse_ini_file($envFile, true);
        }
    }

    /**
     * 读取env配置
     * @param string $key
     * @return string|null
     */
    public static function get($key, $default = null)
    {
        if (is_null(self::$env)) {
            self::reload();
        }
        return isset(self::$env[$key]) ? self::$env[$key] : $default;
    }
}
