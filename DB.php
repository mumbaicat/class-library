<?php


class DB
{
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

    // 分页参数
    private $page = [
        // 数据总条数
        "total" => 0,
        // 多少条记录一页
        "page_size" => 15,
        // 最后一页
        "last_page" => 0,
        // 上一页
        "prev_page" => 0,
        // 当前页
        "curr_page" => 0,
        // 下一页
        "next_page" => 0,
        "next_page_url" => "",
        "prev_page_url" => "",
        // url
        "url" => '',
        // 生成链接
        "links" => null,
        // 数据
        "data" =>[]
    ];

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


    /******************************************************************************
     * 字段，条件，排序，N条
     */
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

    /***************************************************************************************
     *     通常的增删查改
     */
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
    public static function find($id, $primary = 'id')
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
        // 设置条件和绑定参数列表
        $this->setWheresAndBindParam();

        $this->sql = "select count(*) from `{$this->table_name}` {$this->wheres} {$this->order_by} {$this->limit}";

        return $this->converQuery($this->sql, $this->bind_params);
    }

    public function max($field)
    {
        // 设置条件和绑定参数列表
        $this->setWheresAndBindParam();

        $this->sql = "select max({$field}) from `{$this->table_name}` {$this->wheres}";

        return $this->converQuery($this->sql, $this->bind_params);
    }

    public function min($field)
    {
        // 设置条件和绑定参数列表
        $this->setWheresAndBindParam();

        $this->sql = "select min({$field}) from `{$this->table_name}` {$this->wheres}";

        return $this->converQuery($this->sql, $this->bind_params);
    }

    public function avg($field)
    {
        // 设置条件和绑定参数列表
        $this->setWheresAndBindParam();

        $this->sql = "select avg({$field}) from `{$this->table_name}` {$this->wheres}";

        return $this->converQuery($this->sql, $this->bind_params);
    }

    public function sum($field)
    {
        // 设置条件和绑定参数列表
        $this->setWheresAndBindParam();

        $this->sql = "select sum({$field}) from `{$this->table_name}` {$this->wheres}";

        return $this->converQuery($this->sql, $this->bind_params);
    }

    /**
     * 聚合查询
     */
    public function converQuery($sql, $params)
    {
        $stmt = $this->dbh->prepare($sql);

        $stmt->execute($params);


        return $stmt->fetch(PDO::FETCH_NUM)[0];
    }


    /**************************************************************************
     *     分页操作
     */
    /**
     * 分页操作
     * @param $page
     * @param $limit
     */
    public function paginate($perPage = null, $columns = ['*'], $link = '', $page = null)
    {
        // 把分页数组转成对象
        $this->page = json_decode(json_encode($this->page));


        // 初始化分页参数
        $this->initPageParams($perPage, $link);

        // 生成默认分页DOM
        $this->createPageLinks();

        return $this->page;
    }

    /**
     * 初始化分页参数
     * @param $pageSize
     * @param $link
     * @param null $page
     */
    private function initPageParams($pageSize, $link)
    {
        $this->page->page_size = $pageSize;

        // 获取显示页码
        if (isset($_GET['page']))
        {
            $this->page->curr_page = $_GET['page'];
        }
        else
        {
            $this->page->curr_page = 1;
        }


        // 上一页 下一页
        $this->page->prev_page = $this->page->curr_page - 1;
        $this->page->next_page = $this->page->curr_page + 1;

        // 总页数
        $this->page->total = $this->count();

        // 最后一页  总数/每页数目
        $this->page->last_page = intval(ceil($this->page->total / $this->page->page_size));

        // 上一页 下一页 的 URL
        $this->page->prev_page_url = "{$link}?page={$this->page->prev_page}";
        $this->page->next_page_url = "{$link}?page={$this->page->next_page}";
        $this->page->url = $link;


        // 查询当前页对应的数据
        $curr_page_nums = ($this->page->curr_page - 1) * $this->page->page_size;

        // 重新赋值 sql 语句
        $this->sql = "select * from `{$this->table_name}` {$this->wheres} {$this->order_by} limit {$curr_page_nums}, {$this->page->page_size}";

        // 当前页的数据
        $this->page->data = $this->query($this->sql, $this->bind_params);
    }

    private function createPageLinks()
    {
        // 判断上一页 下一页是否可用
        $page = [
            'is_prev_page' => '',
            'is_next_page' => '',
            'content_page' => ''
        ];


        // 显示的按钮数目  大于五页就显示5页，否则就显示总共有多少页
        $showcount = $this->page->last_page >= 5 ? 5 : $this->page->last_page;
        $left = $this->page->curr_page - (int)($showcount - 1)/2;


        if ($left < 1)
        {
            $left = 1;
        }
        elseif($left + $showcount >= $this->page->last_page)
        {
            $left = $this->page->last_page - $showcount + 1;
        }

        $right = $left + $showcount - 1;

        // 后面的2个
        for ($i = $left; $i <= $right; ++$i)
        {
            $active = '';

            if ($i == $this->page->curr_page)
            {
                $active = 'active';
            }

            $page['content_page'] .= "<li class='{$active}'><a href='{$this->page->url}?page={$i}'>{$i}</a></li>";
        }

        // 第一页的时候
        $first_home_label = 'a';
        $last_end_label = 'a';
        if ($this->page->curr_page <= 1)
        {
            $page['is_prev_page'] = 'disabled';

            $first_home_label = 'span';
        }


        // 如果下一页大于最后一页
        if ($this->page->curr_page >= $this->page->last_page)
        {
            $page['is_next_page'] = 'disabled';
            $last_end_label = 'span';
        }



        $dom = <<<page
<ul class="pagination">
    <li class="{$page['is_prev_page']}">
        <{$first_home_label} href="{$this->page->url}?page=1">首页</{$first_home_label}>
    </li>
    <li class="{$page['is_prev_page']}">
        <{$first_home_label} href="{$this->page->prev_page_url}">&laquo;</{$first_home_label}>
    </li>
        {$page['content_page']}
    <li class="{$page['is_next_page']}">
        <{$last_end_label} href="{$this->page->next_page_url}">&raquo;</{$last_end_label}>
    </li>
    <li class="{$page['is_next_page']}">
        <{$last_end_label} href="{$this->page->url}?page={$this->page->last_page}">尾页</{$last_end_label}>
    </li>
</ul>
page;

        $this->page->links = $dom;
        // 存入闭包，方便函数式调用
//        $links = function() use ($dom){
//            return $dom;
//        };
//
//        echo '<pre>';
//        $this->page->links = $links;
//
//        var_dump($links === $this->page->links);


    }

}