<?php


class DB
{
    // PDO connection
    private static $dsn;
    private static $user;
    private static $pwd;

    // 数据库配置
    private static $config = [
        'host' => 'host',
        'database' => 'database',
        'username' => 'username',
        'password' => 'password',
        'charset' => 'charset',
        ];
    // 单例
    private static $instance;


    // 数据库连接
    private $dbh;
    // 数据表名
    private $table_name;
    // 查询条件
    private $wheres = [];
    // 排序
    private $order_by;
    // 绑定的参数列表
    private $bind_params;
    // 查询的字段
    private $field;
    // 查询多少条
    private $limit;
    // 最后生成的 sql
    private $sql;


    /**
     * 单例不允许实例化多个
     */
    final private function __construct()
    {
        list($host, $dbname, $user, $pwd, $charset) = array_values(self::$config);

        // 实例化数据库对象
        $this->dbh = new PDO("mysql:host={$host};dbname={$dbname};charset={$charset}", $user, $pwd);

        // 发生错误时抛出异常
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    final private function __clone(){}
    final private function __sleep(){}


    /**
     * 初始化配置选项
     * @param array $config
     * @throws Exception
     */
    public static function initConfig($config = [])
    {
        // 判断配置是否符合要求   非空则是不符合配置文件要求
        $diff_conf = array_diff_key(self::$config, $config);

        if (!empty($diff_conf))
        {
            $errmsg = '配置项：' . implode(' | ', $diff_conf) . ' 存在错误';
            throw new Exception($errmsg);
        }

        // 读取配置
        array_walk($config, function($v, $k){
            self::$config[$k] = $v;
        });

        self::getInstace();
    }

    /**
     * 查询第一步选择表名
     * @param $table
     * @return mixed
     */
    public static function table($table)
    {
        // 获取实例
        $instance = self::getInstace();

        // 每次查询前重置上一次的数据
        $instance->resetQuery($table);

        // 返回实例
        return $instance;
    }

    /**
     * 重置查询操作
     */
    private function resetQuery($table_name)
    {
        // 表名
        $this->table_name = $table_name;
        // 查询条件
        $this->wheres = [];
        // 排序
        $this->order_by = '';
        // 绑定的参数列表
        $this->bind_params = [];
        // 查询的字段
        $this->field = '*';
        // 查询多少条
        $this->limit = '';
        // 最后生成的 sql
        $this->sql = '';
    }

    /**
     * 单例获取实例
     * @return DB
     */
    private static function getInstace()
    {
        if (is_null(self::$instance))
        {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * 要查询的字段
     * @param array $column 字段名称
     * @return $this
     */
    public function select($column = [])
    {
        if (empty($column))
        {
             $this->field= '*';
        }
        else
        {
            $this->field = implode('`, `', $column);
            // 前后各添加一个反引号
            $this->field = '`' . $this->field . '`';
        }

        return $this;
    }

    /**
     * 查询条件
     * @param $column    条件的列名
     * @param $operator  操作符
     * @param $value     条件值
     */
    public function where($column, $operator, $value)
    {
        $type = '';
        $this->wheres[] = compact('column', 'operator', 'value', 'type');

        return $this;
    }
    public function OrWhere($column, $operator, $value)
    {
        $type = 'or';
        $this->wheres[] = compact('column', 'operator', 'value', 'type');

        return $this;
    }
    public function AndWhere($column, $operator, $value)
    {
        $type = 'and';
        $this->wheres[] = compact('column', 'operator', 'value', 'type');

        return $this;
    }


    /**
     * 排序查询
     * @param $field_name    排序的字段
     * @param string $order  升序还是降序
     * @return $this
     */
    public function orderBy($field_name, $order = 'asc')
    {
        if (empty($this->order_by))
        {
            $this->order_by = " order by `{$field_name}` {$order}";
        }
        else
        {
            $this->order_by .= ", {$field_name} {$order}";
        }

        return $this;
    }

    /**
     * 查询多少条记录
     * @param $limit
     * @param null $offset
     * @return $this
     */
    public function limit($limit, $offset = null)
    {
        if (is_null($offset))
        {
            $this->limit = " limit {$limit}";
        }
        else
        {
            $this->limit = "limit {$limit}, {$offset}";
        }

        return $this;
    }


    /**
     * 查询主要方法 获取结果
     * @return array
     */
    public function get()
    {
        // 设置查询 SQL 语句
        $this->setQueryStatement();

        return $this->query($this->sql, $this->bind_params);
    }


    /**
     * 取第一条记录
     * @return array
     */
    public function first()
    {
        // 设置 limit 为 1
        $this->limit(1);

        // 设置查询 SQL 语句
        $this->setQueryStatement();

        return $this->query($this->sql, $this->bind_params);
    }

    /**
     * 获取查询的语句
     * @return array
     */
    private function setQueryStatement()
    {
        // 设置条件和绑定参数列表
        $this->setWheresAndBindParam();

        $this->sql = "select {$this->field} from `{$this->table_name}` {$this->wheres} {$this->order_by} {$this->limit}";
    }

    /**
     * 设置条件和绑定参数列表
     */
    private function setWheresAndBindParam()
    {
        $wheres = '';

        if (! empty($this->wheres))
        {
            $wheres = 'where ';

            foreach ($this->wheres as $k=>$v)
            {
                $wheres .= "{$v['type']} `{$v['column']}` {$v['operator']} ? ";
                // 放到这里是为了防止其他操作也会修改 bind_params
                $this->bind_params[] = $v['value'];
            }
        }

        $this->wheres = $wheres;
    }


    /**
     * 查询语句
     * @param $sql
     * @param $params
     * @return array
     */
    private function query($sql, $params)
    {
        // 预处理查询
        $stmt = $this->dbh->prepare($sql);

        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * 非查询语句
     * @param $sql
     * @param $params
     * @return int
     */
    private function noneQuery($sql, $params)
    {
        // 预处理查询
        $stmt = $this->dbh->prepare($sql);
        $stmt->execute($params);


        return $stmt->rowCount();
    }


    /**
     * 增加记录
     * @param array $fields 增加的内容（键值对数组）
     * @return int          返回插入后的ID
     */
    public function insert($fields = [])
    {
        $keys = '';
        $insert_values = '';

        if (! empty($fields))
        {
            $keys = implode('`, `', array_keys($fields));

            foreach ($fields as $column=>$value)
            {
                $insert_values .= "?,";
                $this->bind_params[] = $value;
            }

            // 去除多余的逗号
            $insert_values = rtrim($insert_values, ',');
        }

        $this->sql = "insert into {$this->table_name} (`{$keys}`) values ({$insert_values})";
        $insert_count = $this->noneQuery($this->sql, $this->bind_params);

        // 获取插入的记录 ID
        $id = $this->dbh->lastInsertId();

        return $id;
    }

    /**
     * 删除操作 可以配合 where 链式操作
     * @return int  返回删除的记录数
     */
    public function delete()
    {
        // 先获取条件
        $this->setWheresAndBindParam();

        $this->sql = "delete from {$this->table_name} {$this->wheres}";

        $delete_count = $this->noneQuery($this->sql, $this->bind_params);

        return $delete_count;
    }

    /**
     * 按主键查询记录
     * @param $id              主键ID
     * @param string $primary  主键
     * @return mixed
     */
    public function find($id, $primary = 'id')
    {
        $this->sql = "select {$this->field} from {$this->table_name} where `{$primary}`=?";

        // 预处理查询
        $stmt = $this->dbh->prepare($this->sql);

        $stmt->execute(array($id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    /**
     * 修改操作可以配合 where 链式操作
     * @param array $fields 需要修改的字段（键值对数组）
     * @return int          返回修改的记录数
     */
    public function update($fields = [])
    {
        $set = '';

        if (! empty($fields))
        {
            $set = 'set ';

            foreach ($fields as $column => $field)
            {
                $set .= "`$column` = ?,";
                $this->bind_params[] = $field;
            }

            $set = rtrim($set, ',');
        }

        // 先绑定修改的参数， 再获取条件
        $this->setWheresAndBindParam();

        $this->sql = "update {$this->table_name} {$set} {$this->wheres}";


        $update_count = $this->noneQuery($this->sql, $this->bind_params);

        return $update_count;
    }

    /**
     * 获取 SQL 语句 （仅调试使用）
     * @return mixed
     */
    private function getSQL()
    {
        $params = $this->bind_params;
        $sql = $this->sql;

        reset($params);

        while (($pos = strpos($sql, '?')) !== false)
        {
            $sql = substr_replace($sql, current($params), $pos, strlen('?'));

            // 指针往后移动一位
            next($params);
        }

        return $sql;
    }

    /***********************************************************************
     *     聚合函数操作
     */
    public function count()
    {
        // Start assimble Query
        $this->sql = "select count(*) form `{$this->table_name}`";

        converQuery($this->sql);
    }

    /**
     * 聚合查询
     */
    public function converQuery($sql)
    {
        $stmt = $this->dbh->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_NUM)[0];
    }


    /**************************************************************************
     *     分页操作
     */
    /**
     * 分页操作
     * @param $page
     * @param $limit
     * @return array|MareiCollection
     */
    public function paginate($page, $limit)
    {
        // Start assimble Query
        $countSQL = "SELECT COUNT(*) FROM `$this->table`";
        if ($this->where !== null) {
            $countSQL .= $this->where;
        }
        // Start assimble Query
        $stmt = $this->dbh->prepare($countSQL);
        $stmt->execute($this->bindValues);
        $totalRows = $stmt->fetch(PDO::FETCH_NUM)[0];
        // echo $totalRows;
        $offset = ($page-1)*$limit;
        // Refresh Pagination Array
        $this->pagination['currentPage'] = $page;
        $this->pagination['lastPage'] = ceil($totalRows/$limit);
        $this->pagination['nextPage'] = $page + 1;
        $this->pagination['previousPage'] = $page-1;
        $this->pagination['totalRows'] = $totalRows;
        // if last page = current page
        if ($this->pagination['lastPage'] ==  $page) {
            $this->pagination['nextPage'] = null;
        }
        if ($page == 1) {
            $this->pagination['previousPage'] = null;
        }
        if ($page > $this->pagination['lastPage']) {
            return [];
        }
        $this->assimbleQuery();
        $sql = $this->sql . " LIMIT {$limit} OFFSET {$offset}";
        $this->getSQL = $sql;
        $stmt = $this->dbh->prepare($sql);
        $stmt->execute($this->bindValues);
        $this->rowCount = $stmt->rowCount();
        $rows = $stmt->fetchAll(PDO::FETCH_CLASS,'MareiObj');
        $collection= [];
        $collection = new MareiCollection;
        $x=0;
        foreach ($rows as $key => $row) {
            $collection->offsetSet($x++,$row);
        }
        return $collection;
    }

}