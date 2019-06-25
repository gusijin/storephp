<?php

namespace core;
class Router
{
    public static $uri;
    protected static $contrallerName;
    protected static $actionName;

    /**
     * 路由解析方法选择
     */
    public static function bootstrap()
    {
        self::$uri = $_SERVER['REQUEST_URI'];
        if (isset($_GET['r'])) {
            self::parseCommon();
        } else {
            self::parseRewrite();
        }

        self::boot();
    }

    /**
     * 普通路由解析
     */
    public static function parseCommon()
    {
        $router = isset($_GET['r']) ? explode(DIRECTORY_SEPARATOR, $_GET['r']) : [DEFAULT_CONTROLLER, DEFAULT_ACTION];

        self::$contrallerName = ucfirst(strtolower($router[0]));
        self::$actionName = isset($router[1]) ? strtolower($router[1]) : DEFAULT_ACTION;
    }

    /**
     * URL重写路由解析
     */
    public static function parseRewrite()
    {


        if (strpos(self::$uri, "?")) {
            $router = substr(self::$uri, 0, strpos(self::$uri, "?"));
        } else {
            $router = self::$uri;
        }
        if ($router == DIRECTORY_SEPARATOR) {
            $router = [DEFAULT_CONTROLLER, DEFAULT_ACTION];
        }

        $routerUri = trim($router, DIRECTORY_SEPARATOR);
        $routersArr = require CONF_PATH . 'routers.php';
        if (!empty($routersArr['ROUTES'][$routerUri])) {
            $routerUri = $routersArr['ROUTES'][$routerUri]['action'];
        }
        $router = explode(DIRECTORY_SEPARATOR, $routerUri);

        self::$contrallerName = ucfirst(strtolower($router[0]));
        self::$actionName = isset($router[1]) ? strtolower($router[1]) : DEFAULT_ACTION;
    }

    /**
     * 路由执行
     */
    public static function boot()
    {

        self::defineConst();
        $controllerName = 'controller\\' . self::$contrallerName;
        if (!class_exists($controllerName)) {
            echo self::$contrallerName . " does not defined";
            exit();
        }
        $controller = new $controllerName();
        if (!method_exists($controller, self::$actionName)) {
            echo self::$actionName . " does not defined";
            exit();
        }
        call_user_func([
            $controller,
            self::$actionName,
        ]);
    }

    /**
     * 定义常用的全局常量
     */
    public static function defineConst()
    {
        define('CONTROLLER', self::$contrallerName);
        define('ACTION', self::$contrallerName);
        define('LOCAL_URL', createUrl(self::$contrallerName . DIRECTORY_SEPARATOR . self::$actionName));
    }

}