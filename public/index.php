<?php

/**
 * storePHP
 * An open source application development framework for PHP
 * Copyright (c) 2019 - 2020, gusijin
 * @package    storePHP
 * @author    gusijin
 * @copyright    Copyright (c) 2019 - 2020, gusijin
 * @since    Version 1.0.1
 */


if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    die('require PHP > 5.4.0');
};

//框架环境默认为开发环境，上线请关闭
define("DEVELOP_ENV", true);

define('PUBLIC_PATH', str_replace('\\', DIRECTORY_SEPARATOR, __DIR__) . DIRECTORY_SEPARATOR);
define("BASE_PATH", PUBLIC_PATH . '..' . DIRECTORY_SEPARATOR);
define("SYSTEM_PATH", BASE_PATH . 'system' . DIRECTORY_SEPARATOR);
define("APP_PATH", BASE_PATH . 'app' . DIRECTORY_SEPARATOR);
define("CONF_PATH", BASE_PATH . 'config' . DIRECTORY_SEPARATOR);
define("CORE_PATH", SYSTEM_PATH . 'core' . DIRECTORY_SEPARATOR);

require CORE_PATH . 'run.php';
