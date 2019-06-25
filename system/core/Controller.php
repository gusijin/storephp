<?php

namespace core;
abstract class Controller
{

    protected function render($viewFile, $data = null)
    {
        View::display($viewFile, $data);
    }
}