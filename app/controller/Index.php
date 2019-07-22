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
        $useArr = $this->indexModel->getUsersList();
        $data=[
            'sq' => $result,
            'users' => $useArr,
        ];
        $this->render('index/index.html', $data);
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