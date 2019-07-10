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
        //插入数据
        /*$data=['name'=>'gsj2019'];
        DB::table('users')->insert($data); //返回主键id*/

        //批量插入
        /*$fields = array('name');
        for ($i=1; $i<=10; $i++) {
            $rowsArr[] = array("gsj_".mt_rand(100, 500));
        }
        DB::table('users')->batchInsert($fields, $rowsArr);*/


        // 删除数据
        //$res=DB::table('users')->where(['user_id' => 4])->delete();//返回影响条数

        //更新数据
        /*$data=['name'=>'gsj2020'];
        $res=DB::table('users')->where(['user_id'=>4])->update($data); //返回影响条数*/


        //获取sql语句 getSql()
        //$res=DB::table('users')->select("user_id,name")->getSql();

        //全部查询
        //DB::table('users')->select("user_id,name")->fetchAll();

        //查询单行
        //DB::table('users')->select("user_id,name")->where('user_id = ?', 1)->fetch();

        //where条件查询
        $where = [
            //'name'=>'gsj',
            'user_id'=>array('le', 3),
            //'user_id' => array('in', array(1, 2, 3)),
            //'name'=>array('like', '%gsj%'),
            //'user_id'=>array( 'between', "1,3" ),
        ];
        $res=DB::table('users')->where($where)->fetchAll();

        //注：ne不等于、gt大于、ge大于等于、lt小于、le小于等于、like像、not like不像
        //in在范围、not in不在范围、between在…中间、not between不在…中间
        //DB::table('users')->where('user_id = ? and name=?', 1,"gsj")->fetch();
        return $res;
    }
}