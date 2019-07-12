<?php

if (!function_exists('compileConf')) {
    /**
     * 解析一个数组变量,将其各键值定义为常量
     * @param $conf
     */
    function compileConf($conf)
    {
        foreach ($conf as $key => $val) {
            if (is_array($val)) {
                compileConf($val);
            } else {
                define($key, $val);
            }
        }
    }
}

if (!function_exists('createUrl')) {
    /**
     * 获取url
     * @param string $info
     * @return string
     */
    function createUrl($info = '')
    {
        $url_info = explode(DIRECTORY_SEPARATOR, strtolower($info));
        $controller = isset($url_info[1]) ? $url_info[0] : strtolower(CONTROLLER);
        $action = isset($url_info[1]) ? $url_info[1] : $url_info[0];
        if (isset($_GET['r'])) {
            return DIRECTORY_SEPARATOR . 'index.php?r=' . $controller . DIRECTORY_SEPARATOR . $action;
        } else {
            return DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . $action;
        }
    }
}
