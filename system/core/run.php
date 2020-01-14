<?php

namespace core;

//引入公共函数
require_once SYSTEM_PATH . 'common' . DIRECTORY_SEPARATOR . 'function.php';
//引入自动加载类,并注册自动加载函数
require_once CORE_PATH . 'Loader.php';

//公共函数解析配置
spl_autoload_register('core\\Loader::autoLoad');

Router::bootstrap();
