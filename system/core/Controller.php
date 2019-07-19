<?php

namespace core;
abstract class Controller
{

    protected function render($viewFile, $data = null)
    {
        View::display($viewFile, $data);
    }

    public function assign($tplVar, $value = '')
    {
        View::assign($tplVar, $value);
    }
}