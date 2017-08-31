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
    private static $dbh;
    // 数据表名
    private static $table_name;
    // 查询条件
    private static $wheres = [];
    // 查询的字段
    private static $field = '';
    // 最后生成的 sql
    private static $sql;


    /**
     * 单例不允许实例化多个
     */
    final private function __construct()
    {
        list($host, $dbname, $user, $pwd, $charset) = array_values(self::$config);

        // 实例化数据库对象
        self::$dbh = new PDO("mysql:host={$host};dbname={$dbname};charset={$charset}", $user, $pwd);

        // 发生错误时抛出异常
        self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    final private function __clone(){}
    final private function __sleep(){}

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
        // 保存表名
        self::$table_name = $table;

        // 返回实例
        return self::getInstace();
    }


    /**
     * 获取 实例
     * @return PDO
     */
    private static function getInstace()
    {
        if (is_null(self::$instance))
        {
            self::$instance = new self();
        }

        return self::$instance;
    }


    public function select($column = [])
    {
        if (empty($column))
        {
            self::$field = '*';
        }
        else
        {
            self::$field = implode('`, `', $column);
            // 前后各添加一个反引号
            self::$field = '`' . self::$field . '`';
        }


        return $this;
    }

    /**
     * 查询条件
     * @param $column
     * @param $operator
     * @param $value
     */
    public function where($column, $operator, $value)
    {
        $type = '';
        self::$wheres[] = compact('column', 'operator', 'value', 'type');

        return $this;
    }
    public function OrWhere($column, $operator, $value)
    {
        $type = 'or';
        self::$wheres[] = compact('column', 'operator', 'value', 'type');

        return $this;
    }
    public function AndWhere($column, $operator, $value)
    {
        $type = 'and';
        self::$wheres[] = compact('column', 'operator', 'value', 'type');

        return $this;
    }

    public function get()
    {
        // 拼接 SQL
        return self::autoQueryQuote();
    }

    public function first()
    {
        // 拼接 SQL
        $data = self::autoQueryQuote(1);

        if (!is_bool($data))
        {
            $data = $data[0];
        }

        return $data;
    }


    private static function autoQueryQuote($limit = '')
    {
        // 先确定是查询
        $wheres = '';
        $params = [];
        $limit = empty($limit) ? '' : "limit 0, {$limit}";

        if (!empty(self::$wheres))
        {
            $wheres = 'where ';

            foreach (self::$wheres as $k=>$v)
            {
                $wheres .= "{$v['type']} `{$v['column']}` {$v['operator']} ? ";
                $params[] = $v['value'];
            }
        }


        self::$sql = 'select ' . self::$field . ' from `' . self::$table_name . '` ' . $wheres . ' ' . $limit;

        $sta = self::$dbh->prepare(self::$sql);
        // 绑定参数
        $sta->execute($params);
        $data = $sta->fetchAll(PDO::FETCH_ASSOC);

        return $data;
    }


}