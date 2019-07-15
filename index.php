<?php

/**
 * storePHP
 * An open source application development framework for PHP
 * Copyright (c) 2019 - 2020, gusijin
 * @package	storePHP
 * @author	gusijin
 * @copyright	Copyright (c) 2019 - 2020, gusijin
 * @since	Version 1.0.0
 */


if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    die('storephp框架需要使用PHP5.4以上版本哦');
};

//框架环境默认为开发环境，上线请关闭
define("DEVELOP_ENV", true);

//定义基本路径
define("BASE_PATH", str_replace('\\', DIRECTORY_SEPARATOR, __DIR__) . DIRECTORY_SEPARATOR);
//定义系统核心路径
define("SYSTEM_PATH", BASE_PATH . 'system' . DIRECTORY_SEPARATOR);
//定义应用路径
define("APP_PATH", BASE_PATH . 'app' . DIRECTORY_SEPARATOR);
//定义系统配置文件路径
define("CONF_PATH", BASE_PATH . 'config' . DIRECTORY_SEPARATOR);
//定义基础类文件路径
define("CORE_PATH", SYSTEM_PATH . 'core' . DIRECTORY_SEPARATOR);

require CORE_PATH . 'run.php';
