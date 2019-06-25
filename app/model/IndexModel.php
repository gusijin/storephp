<?php

namespace model;

use core\Model;
use database\Mysqli;

class IndexModel extends Model
{

    protected $db;

    public function __construct()
    {
        $this->db = Mysqli::getInstance();
    }

    public function doSomething()
    {
        $sq = "welcome to storephp!";
        return $sq;
    }

    public function getUsersList()
    {
        return $this->db->getAll("select * from users");
    }
}