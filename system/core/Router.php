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
        self::setDebug();

        self::$uri = $_SERVER['REQUEST_URI'];
        if (isset($_GET['r'])) {
            self::parseByCommonRouter();
        } else {
            self::parseRewrite();
        }

        self::boot();
    }

    /**
     * 普通路由解析
     */
    public static function parseByCommonRouter()
    {
        $router = isset($_GET['r']) ? explode(DIRECTORY_SEPARATOR, $_GET['r']) : [Env::get('DEFAULT_CONTROLLER'), Env::get('DEFAULT_ACTION')];

        self::$contrallerName = ucfirst(strtolower($router[0]));
        self::$actionName = isset($router[1]) ? strtolower($router[1]) : Env::get('DEFAULT_ACTION');
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
            $router = [Env::get('DEFAULT_CONTROLLER'), Env::get('DEFAULT_ACTION')];
        }
        is_string($router) && $routerUri = trim($router, DIRECTORY_SEPARATOR);

        //自定义路径
        $routersArr = require CONF_PATH . 'routes.php';
        if (!empty($routerUri) && !empty($routersArr['ROUTES'][$routerUri])) {
            $routerUri = $routersArr['ROUTES'][$routerUri]['action'];
        }
        !is_array($router) && $router = explode(DIRECTORY_SEPARATOR, $routerUri);

        self::$contrallerName = ucfirst(strtolower($router[0]));
        self::$actionName = isset($router[1]) ? strtolower($router[1]) : Env::get('DEFAULT_ACTION');
    }

    /**
     * 路由执行
     */
    public static function boot()
    {

        define('CONTROLLER', self::$contrallerName);
        define('ACTION', self::$contrallerName);
        define('LOCAL_URL', createUrl(self::$contrallerName . DIRECTORY_SEPARATOR . self::$actionName));

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


    private static function setDebug()
    {
        if (Env::get('DEBUG')) {
            ini_set('display_errors', 1);
            error_reporting(-1);
        } else {
            ini_set('display_errors', 0);
        }
    }

}