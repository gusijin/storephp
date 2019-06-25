<?php

if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    die('storephp框架需要使用PHP5.4以上版本哦');
};

//框架环境默认为开发环境，上线请关闭
define("DEVELOP_ENV", true);
if (DEVELOP_ENV) {
    ini_set('display_errors', 1);
    error_reporting(-1);
} else {
    ini_set('display_errors', 0);
}

//定义基本路径
define("BASE_PATH", str_replace('\\', DIRECTORY_SEPARATOR, __DIR__) . DIRECTORY_SEPARATOR);
//定义系统核心路径
define("SYSTEM_PATH", BASE_PATH . 'system' . DIRECTORY_SEPARATOR);
//定义应用路径
define("APP_PATH", BASE_PATH . 'app' . DIRECTORY_SEPARATOR);
//定义系统配置文件路径
define("CONF_PATH", SYSTEM_PATH . 'config' . DIRECTORY_SEPARATOR);
//定义基础类文件路径
define("CORE_PATH", SYSTEM_PATH . 'core' . DIRECTORY_SEPARATOR);

require CORE_PATH . 'run.php';
