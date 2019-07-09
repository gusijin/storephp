<?php

namespace database;

class DB
{
    public static $__pdo = null;        // 默认PDO对象
    public static $__is_mysql = null;        // 默认是否mysql数据库
    public static $__dsn = "mysql:host=%s;dbname=%s;charset=%s;"; // DSN
    public static $__host = "";    // 默认主机
    public static $__username = "";        // 默认账户
    public static $__password = "";    // 默认密码
    public static $__dbname = "";        // 默认数据库名称
    public static $__charset = "";        // 默认字符集
    public static $__prefix = "";            // 默认表前缀
    protected $_pdo = null;        // PDO对象
    protected $_is_mysql = null;        // 默认是否mysql数据库
    protected static $_prefix = null;        // 表前缀
    protected static $_table = null;        // 表名
    protected static $_alias = null;        // 表别名
    protected static $_fulltable = null;        // 表全名
    protected static $_instance = false;
    protected $_pk = null;        // 主键
    private $_keywords = array();        // keywords
    private $_columns = array();        // columns
    private $_joins = array();        // joins
    private $_wheres = array();        // where
    private $_wheres_params = array();        // where params
    private $_groups = array();        // group
    private $_havings = array();        // having
    private $_havings_params = array();        // having params
    private $_orders = array();        // order
    private $_limit = null;        // limit
    private $_offset = null;        // offset
    private $_for_update = "";            // read lock
    private $_lock_in_share_mode = "";            // write lock
    private $_count_wheres = array();        // count where
    private $_count_wheres_params = array();        // count where params
    private $_exp = array('eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=',
        'not like' => 'NOT LIKE', 'like' => 'LIKE', 'in' => 'IN', 'not in' => 'NOT IN', 'between' => 'BETWEEN',
        'not between' => 'NOT BETWEEN',);
    public $_sql = "";            // sql
    public $_params = array();        // params

    public static function getInstance()
    {
        if (self::$_instance == false) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    // 获取参数类型
    private static function type($param)
    {
        static $types = array(
            "boolean" => \PDO::PARAM_BOOL,
            "NULL" => \PDO::PARAM_NULL,
            "double" => \PDO::PARAM_INT,
            "integer" => \PDO::PARAM_INT,
            "string" => \PDO::PARAM_STR,
        );
        $type = gettype($param);
        return array_key_exists($type, $types) ? $types[$type] : \PDO::PARAM_STR;
    }

    public function __construct()
    {
        self::$__host = DB_HOST ? DB_HOST : '127.0.0.1';
        self::$__username = DB_USER ? DB_USER : 'root';
        self::$__password = DB_PWD ? DB_PWD : 'root';
        self::$__dbname = DB_NAME ? DB_NAME : 'test';
        self::$__charset = DB_CHATSET ? DB_CHATSET : 'utf8';
    }

    // 设置表前缀
    public function prefix($prefix = "")
    {
        self::$_prefix = $prefix;
        self::$_fulltable = self::$_prefix . self::$_table;
        return $this;
    }

    // 设置表名
    public static function table($table = "")
    {
        self::$_table = $table;
        self::$_fulltable = self::$_prefix . self::$_table;
        return self::getInstance();
    }

    // 设置表别名
    public function alias($alias = "")
    {
        self::$_alias = $alias;
        return $this;
    }

    // 设置主键名称
    public function pk($pk = "id")
    {
        $this->_pk = $pk;
        return $this;
    }

    // 设置MySQL关键字
    public function keyword($keyword)
    {
        if ($this->is_mysql()) {
            $this->_keywords[] = $keyword;
        }
        return $this;
    }

    // 设置SQL_CALC_FOUND_ROWS关键字
    public function calcFoundRows()
    {
        return $this->keyword("SQL_CALC_FOUND_ROWS");
    }

    // column返回的列
    public function select($column)
    {
        $this->_columns[] = $column;
        return $this;
    }

    // leftjoin连表查询
    public function leftjoin($join, $cond)
    {
        list($table, $alias) = explode(" AS ", $join);
        $prefix = strpos($table, "`") === 0 ? "" : self::$__prefix;
        if (empty($alias)) {
            $this->_joins[] = sprintf("`%s%s` ON %s", $prefix, $table, $cond);
        } else {
            $this->_joins[] = sprintf("`%s%s` AS `%s` ON %s", $prefix, $table, $alias, $cond);
        }
        return $this;
    }

    // where查询条件
    public function where($where)
    {
        if (is_array($where)) {
            $this->arrayWhere($where);
        } else {
            $args = array_slice(func_get_args(), 1);
            $this->stringWhere($where, $args);
        }
        return $this;
    }

    private function stringWhere($where, $args)
    {
        $ws = explode("?", $where);
        $where = array_shift($ws);
        $params = array();
        foreach ($ws as $i => $w) {
            if (!empty($args) && is_array($args[$i])) {
                $where .= "?" . str_repeat(",?", count($args[$i]) - 1) . $w;
                $params = array_merge($params, $args[$i]);
            } else {
                $where .= "?" . $w;
                !empty($args) && $params[] = $args[$i];
            }
        }
        $this->_wheres[] = $where;
        $this->_wheres_params = array_merge($this->_wheres_params, $params);
    }

    /**
     * where数组分析
     * @param $where
     */
    private function arrayWhere($where)
    {
        $whereStr = '';
        $operate = ' and ';
        foreach ($where as $key => $val) {
            $whereStr .= $this->parseWhereItem($key, $val);
            $whereStr .= $operate;
        }
        $whereStr = substr($whereStr, 0, -strlen($operate));
        $this->stringWhere($whereStr, '');
    }

    // where子单元分析
    private function parseWhereItem($key, $val)
    {
        $whereStr = '';
        if (is_array($val)) {
            if (is_string($val[0])) {
                $exp = strtolower($val[0]);
                if (preg_match('/^(eq|neq|gt|egt|lt|elt)$/', $exp)) { // 比较运算
                    $whereStr .= $key . ' ' . $this->_exp[$exp] . ' ?';
                    $this->_wheres_params[] = $val[1];
                } elseif (preg_match('/^(not like|like)$/', $exp)) { // 模糊查找
                    if (is_array($val[1])) {
                        $likeLogic = isset($val[2]) ? strtolower($val[2]) : 'OR';
                        if (in_array($likeLogic, array('AND', 'OR', 'XOR'))) {
                            $like = array();
                            foreach ($val[1] as $item) {
                                $like[] = $key . ' ' . $this->_exp[$exp] . ' ?';
                                $this->_wheres_params[] = $item;
                            }
                            $whereStr .= '(' . implode(' ' . $likeLogic . ' ', $like) . ')';
                        }
                    } else {
                        $whereStr .= $key . ' ' . $this->_exp[$exp] . ' ?';
                        $this->_wheres_params[] = $val[1];
                    }
                } elseif (preg_match('/^(not in|in)$/', $exp)) { // IN 运算
                    if (is_string($val[1])) {
                        $val[1] = explode(',', $val[1]);
                    }
                    $zone = '';
                    foreach ($val[1] as $valInItem) {
                        $zone .= "?,";
                        $this->_wheres_params[] = $valInItem;
                    }
                    $zone = rtrim($zone, ",");
                    $whereStr .= $key . ' ' . $this->_exp[$exp] . " ({$zone})";
                } elseif (preg_match('/^(not between|between)$/', $exp)) { // BETWEEN运算
                    $data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
                    $whereStr .= $key . ' ' . $this->_exp[$exp] . ' ? AND ?';
                    $this->_wheres_params[] = $data[0];
                    $this->_wheres_params[] = $data[1];
                } else {
                    throw new Exception('not_support_expressions_' . $val[0] . '=>' . $val[1]);
                }
            } else {
                $count = count($val);
                $rule = isset($val[$count - 1]) ? (is_array($val[$count - 1]) ? strtolower($val[$count - 1][0]) : strtolower($val[$count - 1])) : '';
                if (in_array($rule, array('AND', 'OR', 'XOR'))) {
                    $count = $count - 1;
                } else {
                    $rule = 'AND';
                }
                for ($i = 0; $i < $count; $i++) {
                    $whereStr .= $this->parseWhereItem($key, $val[$i]) . ' ' . $rule . ' ';
                }
                $whereStr = '( ' . substr($whereStr, 0, -4) . ' )';
            }
        } else {
            $whereStr .= $key . ' = ?';
            $this->_wheres_params[] = $val;
        }
        return $whereStr;
    }

    // group分组
    public function group($group)
    {
        $this->_groups[] = $group;
        return $this;
    }

    // having过滤条件
    public function having($having)
    {
        $args = array_slice(func_get_args(), 1);
        $ws = explode("?", $having);
        $having = array_shift($ws);
        $params = array();
        foreach ($ws as $i => $w) {
            if (is_array($args[$i])) {
                $having .= "?" . str_repeat(",?", count($args[$i]) - 1) . $w;
                $params = array_merge($params, $args[$i]);
            } else {
                $having .= "?" . $w;
                $params[] = $args[$i];
            }
        }
        $this->_havings[] = $having;
        $this->_havings_params = array_merge($this->_havings_params, $params);
        return $this;
    }

    // order排序
    public function order($order)
    {
        $this->_orders[] = $order;
        return $this;
    }

    // limit数据
    public function limit($limit)
    {
        $this->_limit = intval($limit);
        return $this;
    }

    // offset偏移
    public function offset($offset)
    {
        $this->_offset = intval($offset);
        return $this;
    }

    // 独占锁，不可读不可写
    public function forUpdate()
    {
        $this->_for_update = " FOR UPDATE";
        return $this;
    }

    // 共享锁，可读不可写
    public function lockInShareMode()
    {
        $this->_lock_in_share_mode = " LOCK IN SHARE MODE";
        return $this;
    }

    // 设置和获取PDO对象
    public function pdo($pdo = null)
    {
        if ($pdo !== null && is_a($pdo, "\\PDO")) {
            $this->_pdo = $pdo;
            $this->_is_mysql = $this->_pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === "mysql";
            return $this;
        }
        if (isset($this->_pdo)) {
            return $this->_pdo;
        }
        if (isset(self::$__pdo)) {
            return self::$__pdo;
        }
        $dsn = sprintf(self::$__dsn, self::$__host, self::$__dbname, self::$__charset);
        $options = array(
            \PDO::ATTR_PERSISTENT => true,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_STRINGIFY_FETCHES => false,
            \PDO::ATTR_EMULATE_PREPARES => false,
        );
        self::$__pdo = new \PDO($dsn, self::$__username, self::$__password, $options);
        self::$__is_mysql = self::$__pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === "mysql";
        return self::$__pdo;
    }

    // 获取当前PDO是否是mysql数据库
    public function is_mysql($is_mysql = null)
    {
        if ($is_mysql === null) {
            return $this->_is_mysql !== null ? $this->_is_mysql : (self::$__is_mysql !== null ? self::$__is_mysql : false);
        }
        $this->_is_mysql = $is_mysql;
        return $this;
    }

    // 事务开始
    public function begin()
    {
        return $this->pdo()->beginTransaction();
    }

    // 事务提交
    public function commit()
    {
        return $this->pdo()->commit();
    }

    // 事务回滚
    public function rollBack()
    {
        return $this->pdo()->rollBack();
    }

    // sql查询
    public function query($sql)
    {
        $params = array_slice(func_get_args(), 1);
        return $this->vquery($sql, $params);
    }

    // sql查询
    public function vquery($sql, $params = array())
    {
        if (strpos($sql, "'") !== false || strpos($sql, '"') !== false) {
            throw new \LogicException("query is not right");
        }
        $this->_sql = $this->doTheSql($params, $sql);

        $this->_params = $params;
        $stmt = $this->pdo()->prepare($sql);
        foreach ($params as $i => $param) {
            $stmt->bindValue($i + 1, $param, self::type($param));
        }
        $stmt->executeResult = $stmt->execute();
        $this->reset();
        return $stmt;
    }

    // 组装查询数据
    private function assembleData($columns = null, $isGetSql = false)
    {
        if (!empty($columns)) {
            $this->_columns[] = $columns;
        }
        $keywords = empty($this->_keywords) ? "" : " " . implode(" ", $this->_keywords);
        $columns = empty($this->_columns) ? "*" : implode(", ", $this->_columns);
        $table = self::$_fulltable . (!empty(self::$_alias) && !empty($this->_joins) ? "` AS `" . self::$_alias : "");
        $joins = empty($this->_joins) ? "" : " LEFT JOIN " . implode(" LEFT JOIN ", $this->_joins);
        $wheres = empty($this->_wheres) ? "" : " WHERE " . implode(" AND ", $this->_wheres);
        $groups = empty($this->_groups) ? "" : " GROUP BY " . implode(", ", $this->_groups);
        $havings = empty($this->_havings) ? "" : " HAVING " . implode(" AND ", $this->_havings);
        $orders = empty($this->_orders) ? "" : " ORDER BY " . implode(", ", $this->_orders);
        $limit = !isset($this->_limit) ? "" : " LIMIT ?";
        $offset = !isset($this->_offset) ? "" : " OFFSET ?";
        $forUpdate = $this->_for_update;
        $lockInShareMode = $this->_lock_in_share_mode;
        $sql = sprintf("SELECT%s %s FROM `%s`%s%s%s%s%s%s%s%s%s", $keywords, $columns, $table, $joins, $wheres, $groups, $havings, $orders, $limit, $offset, $forUpdate, $lockInShareMode);
        $params = array_merge($this->_wheres_params, $this->_havings_params);
        if (isset($this->_limit)) {
            $params[] = $this->_limit;
        }
        if (isset($this->_offset)) {
            $params[] = $this->_offset;
        }
        $this->_count_wheres = $this->_wheres;
        $this->_count_wheres_params = $this->_wheres_params;

        if ($isGetSql) {
            $this->_sql = $this->doTheSql($params, $sql);
            return $this->_sql;
        }

        return $this->vquery($sql, $params);
    }

    /**
     * 拼接sql
     * @param $params
     * @param $sql
     * @return mixed
     */
    private function doTheSql($params, $sql)
    {
        foreach ($params as $paramsItem) {
            is_string($paramsItem) && $paramsItem = '"' . $paramsItem . '"';
            $sql = substr_replace($sql, $paramsItem, strpos($sql, "?"), strlen("?"));
        }
        return $sql;
    }

    public function fetchAll()
    {
        return $this->assembleData()->fetchAll();
    }

    public function fetch()
    {
        return $this->assembleData()->fetch();
    }

    // 添加数据
    public function insert(array $data)
    {
        $cols = array();
        $sets = array();
        $params = array();
        foreach ($data as $col => $val) {
            $cols[] = sprintf("`%s`", $col);
            $sets[] = sprintf("`%s` = ?", $col);
            $params[] = $val;
        }
        if ($this->is_mysql()) {
            $sql = sprintf("INSERT INTO `%s` SET %s", self::$_fulltable, implode(", ", $sets));
        } else {
            $sql = sprintf("INSERT INTO `%s` (%s) VALUES (?%s)", self::$_fulltable, implode(", ", $cols), str_repeat(", ?", count($cols) - 1));
        }
        $affectRow = $this->vquery($sql, $params)->rowCount();
        if ($affectRow) {
            return $this->lastInsertId();
        }
        return false;
    }

    // 获取自增ID
    public function lastInsertId()
    {
        return $this->pdo()->lastInsertId();
    }

    // 获取符合条件的行数
    public function count()
    {
        if ($this->is_mysql()) {
            return $this->vquery("SELECT FOUND_ROWS()")->fetchColumn();
        } else {
            $wheres = empty($this->_count_wheres) ? "" : " WHERE " . implode(" AND ", $this->_count_wheres);
            $sql = sprintf("SELECT count(*) FROM `%s`%s", self::$_fulltable, $wheres);
            return $this->vquery($sql, $this->_count_wheres_params)->fetchColumn();
        }
    }

    // 批量插入数据
    public function batchInsert(array $columns, array &$rows, $batch = 100)
    {
        $column = implode("`,`", $columns);
        $value = ",(?" . str_repeat(",?", count($columns) - 1) . ")";
        $params = array();
        $len = count($rows);
        for ($i = 0; $i < $len; $i++) {
            $params = array_merge($params, $rows[$i]);
            if (($i + 1) % $batch == 0) {
                $sql = sprintf("INSERT INTO `%s` (`%s`) VALUES %s%s", self::$_fulltable, $column, substr($value, 1), str_repeat($value, $batch - 1));
                $this->vquery($sql, $params);
                $params = array();
            }
        }
        if ($len % $batch > 0) {
            $sql = sprintf("INSERT INTO `%s` (`%s`) VALUES %s%s", self::$_fulltable, $column, substr($value, 1), str_repeat($value, $len % $batch - 1));
            $this->vquery($sql, $params);
        }
        return $this;
    }

    // 更新数据
    public function update(array $data)
    {
        if (empty($this->_wheres)) {
            throw new \LogicException("where is empty!");
        }
        $sets = array();
        $params = array();
        foreach ($data as $col => $val) {
            $sets[] = sprintf("`%s` = ?", $col);
            $params[] = $val;
        }
        $wheres = " WHERE " . implode(" AND ", $this->_wheres);
        $orders = empty($this->_orders) ? "" : " ORDER BY " . implode(", ", $this->_orders);
        $limit = !isset($this->_limit) ? "" : " LIMIT ?";
        $sql = sprintf("UPDATE `%s` SET %s%s%s%s", self::$_fulltable, implode(", ", $sets), $wheres, $orders, $limit);
        $params = array_merge($params, $this->_wheres_params);
        if (isset($this->_limit)) {
            $params[] = $this->_limit;
        }
        return $this->vquery($sql, $params)->rowCount();
    }

    // 替换数据
    public function replace(array $data)
    {
        $sets = array();
        $params = array();
        foreach ($data as $col => $val) {
            $sets[] = sprintf("`%s` = ?", $col);
            $params[] = $val;
        }
        $sql = sprintf("REPLACE INTO `%s` SET %s", self::$_fulltable, implode(", ", $sets));
        return $this->vquery($sql, $params);
    }

    // 删除数据
    public function delete()
    {
        if (empty($this->_wheres)) {
            throw new \LogicException("WHERE is empty!");
        }
        $wheres = " WHERE " . implode(" AND ", $this->_wheres);
        $orders = empty($this->_orders) ? "" : " ORDER BY " . implode(", ", $this->_orders);
        $limit = !isset($this->_limit) ? "" : " LIMIT ?";
        $sql = sprintf("DELETE FROM `%s`%s%s%s", self::$_fulltable, $wheres, $orders, $limit);
        $params = $this->_wheres_params;
        if (isset($this->_limit)) {
            $params[] = $this->_limit;
        }
        return $this->vquery($sql, $params)->rowCount();
    }

    // 重置所有
    public function reset()
    {
        $this->_debug = false;
        $this->_keywords = array();
        $this->_columns = array();
        $this->_joins = array();
        $this->_wheres = array();
        $this->_wheres_params = array();
        $this->_groups = array();
        $this->_havings = array();
        $this->_havings_params = array();
        $this->_orders = array();
        $this->_limit = null;
        $this->_offset = null;
        $this->_for_update = "";
        $this->_lock_in_share_mode = "";
        return $this;
    }

    // page分页
    public function page($page = null, $pagesize = 15)
    {
        $page = intval(isset($page) ? $page : (isset($_GET["p"]) ? $_GET["p"] : 1));
        $pagesize = intval($pagesize);
        $this->_limit = $pagesize;
        $this->_offset = ($page - 1) * $pagesize;
        return $this;
    }

    // pagination分页
    public function pagination($pagesize = 15)
    {
        $args = array_slice(func_get_args(), 1);
        $pagination = new Pagination($this->count(), null, $pagesize);
        if (!empty($format)) {
            call_user_func_array(array($pagination, "setStatic"), $args);
        }
        return $pagination->show();
    }

    // 将选中行的指定字段加一
    public function plus($col, $val = 1)
    {
        $args = array_slice(func_get_args(), 2);
        $sets = array(sprintf("`%s` = `%s` + ?", $col, $col));
        $vals = array($val);
        while (count($args) > 1) {
            $col = array_shift($args);
            $val = array_shift($args);
            $sets[] = sprintf("`%s` = `%s` + ?", $col, $col);
            $vals[] = $val;
        }
        if (empty($this->_wheres)) {
            throw new \LogicException("WHERE is empty!");
        }
        $wheres = " WHERE " . implode(" AND ", $this->_wheres);
        $sql = sprintf("UPDATE `%s` SET %s%s", self::$_fulltable, implode(", ", $sets), $wheres);
        $params = array_merge($vals, $this->_wheres_params);
        $this->vquery($sql, $params);
        return $this;
    }

    // 将选中行的指定字段加一
    public function incr($col, $val = 1)
    {
        if (empty($this->_wheres)) {
            throw new \LogicException("WHERE is empty!");
        }
        $wheres = " WHERE " . implode(" AND ", $this->_wheres);
        $sql = sprintf("UPDATE `%s` SET `%s` = last_insert_id(`%s` + ?)%s", self::$_fulltable, $col, $col, $wheres);
        $params = array_merge(array($val), $this->_wheres_params);
        $this->vquery($sql, $params);
        return $this->pdo()->lastInsertId();
    }

    // 根据主键查找行
    public function find($id)
    {
        return $this->where(sprintf("`%s` = ?", $this->_pk), $id)->assembleData()->fetch();
    }

    // 添加数据
    public function add(array $data)
    {
        return $this->insert($data);
    }

    // 编辑数据
    public function edit(array $data)
    {
        if (!array_key_exists($this->_pk, $data)) {
            throw new \LogicException("\$data must contains column {$this->_pk}!");
        }
        $pk_val = $data[$this->_pk];
        unset($data[$this->_pk]);
        return $this->where(sprintf("`%s` = ?", $this->_pk), $pk_val)->update($data);
    }

    // 添加数据
    public function del($id)
    {
        return $this->where(sprintf("`%s` = ?", $this->_pk), $id)->delete();
    }

    // 保存数据,自动判断是新增还是更新
    public function save(array $data)
    {
        if (array_key_exists($this->_pk, $data)) {
            return $this->edit($data);
        } else {
            return $this->add($data);
        }
    }

    // 获取外键数据
    public function foreignKey(array $rows, $foreign_key, $columns = "*")
    {
        $ids = array_column($rows, $foreign_key);
        if (empty($ids)) {
            return new \PDOStatement();
        }
        $ids = array_unique($ids);
        return $this->where(sprintf("`%s` IN (?)", $this->_pk), $ids)->assembleData($columns);
    }

    //获取sql语句
    public function getSql()
    {
        return $this->assembleData('', true);
    }
}
