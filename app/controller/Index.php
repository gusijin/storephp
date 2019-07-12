<?php

namespace Controller;

use core\Controller;
use model\IndexModel;


class Index extends Controller
{

    private $indexModel;

    public function __construct()
    {
        $this->indexModel = new IndexModel();
    }

    /**
     *
     */
    public function index()
    {
        $result = $this->indexModel->doSomething();

        $this->render('index/index.html', ['sq' => $result]);
    }

    public function test()
    {
        $res = $this->indexModel->getUsersList();
        echo "<pre>";
        print_r($res);
        die();
    }

    public function shop()
    {
        $arr = $_GET;
        echo "<pre>";
        print_r($arr);
        die();
    }
}