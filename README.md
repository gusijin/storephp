# StorePHP

### author
gusijin(古思金)


### 简介

StorePHP是一个简单、快速的轻量级国产PHP开发框架，诞生于2019年。

StorePHP可以支持windows/Unix/Linux等服务器环境，正式版需要PHP5.4以上版本支持，支持MySql数据库。

StorePHP是学习框架原理最佳选择，代码容易理解、复用性强

### Nginx 配置
nginx.conf文件
配置要目录到public

```
location / {
	root   /www/storephp/public;
	index  index.php index.html index.htm;
	if (!-e $request_filename) {
		rewrite  ^(.*)$  /index.php?s=/$1  last;
		break;
	}
}
location ~ \.php(.*)$ {
    fastcgi_pass   127.0.0.1:9000;
    fastcgi_index  index.php;
    fastcgi_param  SCRIPT_FILENAME  /www/storephp/public$fastcgi_script_name;
    include        fastcgi_params;
}

```

### 路由实现


http://url?r=index/test

或者

http://url/index/test

其中index为控制器，test为方法

【自定义路由】

system/config/routers.php

```php
return [
    'ROUTES' => [
        'shop.html' => ['action' => 'index/shop'],
    ],
];

```
可直接访问 http://url/shop.html

### 数据库配置
env文件

```
DEVELOP_ENV = true
DEFAULT_CONTROLLER = index
DEFAULT_ACTION = index

DB_HOST = 0.0.0.0
DB_PORT = 3306
DB_USER = root
DB_PWD = 123456
DB_NAME = test
DB_CHATSET = utf8
```


### 数据库操作

增删改查，mysql数据库操作


```php

//使用命名空间
use database\DB;


//插入数据
$data=['name'=>'gsj2019'];
DB::table('users')->insert($data); //返回主键id


//批量插入
$fields = array('name');
for ($i=1; $i<=10; $i++) {
    $rowsArr[] = array("gsj_".mt_rand(100, 500));
}
DB::table('users')->batchInsert($fields, $rowsArr);


// 删除数据
DB::table('users')->where(['user_id' => 4])->delete();//返回影响条数



//更新数据
$data=['name'=>'gsj2020'];
$res=DB::table('users')->where(['user_id'=>4])->update($data); //返回影响条数



//全部查询 fetchAll()
DB::table('users')->select("user_id,name")->fetchAll();

//查询单行 fetch()
DB::table('users')->select("user_id,name")->where('user_id = ?', 1)->fetch();

//where条件查询
$where = [
    'name'=>'gsj',
    //'user_id'=>array('lt', 3),
    //'user_id' => array('in', array(1, 2, 3)),
    //'name'=>array('like', '%gsj%'),
    //'user_id'=>array( 'between', "1,3" ),
];
$res=DB::table('users')->where($where)->fetchAll();
//注：ne不等于、gt大于、ge大于等于、lt小于、le小于等于、like像、not like不像
//in在范围、not in不在范围、between在…中间、not between不在…中间

DB::table('users')->where('user_id = ? and name=?', 1,"gsj")->fetch();


//获取sql语句 getSql()
DB::table('users')->select("user_id,name")->getSql();



```

### 模板操作

控制器赋值

```php
$data=[
    'sq' => $result,
    'users' => $useArr,
];
$this->render('index/index.html', $data);
```


模板显示数据
```html
<!--显示变量-->
{$sq}
<!--foreach-->
{if $users}
    {foreach from=$users key=users_key item=users_item}
        {$users_key} => {$users_item.name}
    {/foreach}
{/if}
```