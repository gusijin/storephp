<?php

namespace model;

use core\Model;
use database\DB;

class IndexModel extends Model
{

    public function doSomething()
    {
        $sq = "welcome to storephp!";
        return $sq;
    }

    public function getUsersList()
    {
        $where=[
            'user_id'=>array('in', array(1, 2)),
            //'name'=>'11ss2',
        ];
        /*'user_id = ?', 1
        'user_id = ? and name=?', 1,"11ss2"*/
        $res = DB::table('users')->where($where)->select()->fetchAll();
        return $res;
    }
}