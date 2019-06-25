<?php

namespace core;
class View
{
    private static $templateFile;

    /**
     * 展示页面
     * @param $viewFile
     * @param $data
     */
    public static function display($viewFile, $data)
    {

        if (is_array($data)) {
            extract($data);
        }

        ob_start();
        ob_implicit_flush(0);
        include self::checkTemplate($viewFile);
        $content = ob_get_clean();

        echo $content;
    }


    /**
     * 验证模板文件的存在
     * @param $viewFile
     * @return string
     */
    public static function checkTemplate($viewFile)
    {
        $viewFolder = APP_PATH . 'view' . DIRECTORY_SEPARATOR;
        if (empty($viewFile)) {
            self::$templateFile = $viewFolder . CONTROLLER . DIRECTORY_SEPARATOR . ACTION . VIEW_EXT;
        } else {
            $viewFileArr = explode(DIRECTORY_SEPARATOR, $viewFile);
            self::$templateFile = $viewFolder . $viewFileArr[0] . DIRECTORY_SEPARATOR . $viewFileArr[1];
        }
        if (file_exists(self::$templateFile)) {
            return self::$templateFile;
        } else {
            //抛出模板文件不存在异常
            echo "template file does not exist";
            exit();
        }
    }


}