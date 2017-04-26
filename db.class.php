<?php
    /**
     * 更新说明
     * @author gp·s
     * @version 1.0
     */
    class DB
    {
        private $dbh;           // 数据库句柄
        private $statement;     // 流对象
        private $result;        // 结果集
        
        private $error_num = 0;     // 错误号
        private $error_message; // 错误消息
        
        
        private function rollBack()
        {
            // 开启事务
            $this->dbh->beginTransaction();
            
            // 加入数据库原有10条
            $result1 = $this->dbh->exec("inset into t_name value('test')");   // 1
            $result2 = $this->dbh->exec("delete from t_name");                // 返回10 
            $result3 = $this->dbh->exec("inset into ");                       // 没有表名 失败 返回 0
            
            // 必须要三条都执行成功才能执行 否则回滚
            if ($result1 && $result2 && $result3)
            {
                // 提交事务
                $this->dbh->commit();
            }
            else
            {
                // 回滚
                $this->rollBack();
            }
            
        }
        
        /**
         *
         * @param string $dsn 数据源名称或叫做 DSN，包含了请求连接到数据库的信息。
         * @param string $username DSN字符串中的用户名。对于某些PDO驱动，此参数为可选项。
         * @param string $password DSN字符串中的密码。对于某些PDO驱动，此参数为可选项。
         * @param array $driver_options 一个具体驱动的连接选项的键=>值数组。
         */
        public function __construct($dsn, $username = '', $password = '', $driver_options = array())
        {
            try
            {
                $this->dbh = new PDO($dsn, $username, $password);
            }
            catch (PDOException $e)
            {
                $this->error_num = $e->getCode();
                $this->error_message = $e->getMessage(); // 取得文本化的错误信息
                                       // var_dump($e->getCode()); // 取得 SQLSTATE 错误代号
                                       // var_dump($e->getFile()); // 取得发生异常的文件名
                                       // var_dump($e->getLine()); // 取得 PHP 程序产生异常的代码所在行号
                                       // var_dump($e->getTrace()); // backtrace();数组
                                       // var_dump($e->getTraceAsString()); // 取得已格成化成字符串的 getTrace() 信息
                $error = '{"error_num":"' . $this->error_num . '","error_message":"' . $this->error_message . '"}';
                die($error);
            }
        }
        
        /**
         * 获取结果集操作对象
         * @return PDOStatement
         */
        public function getSatement()
        {
            return $this->statement;
        }
        
        /**
         * 获取错误号
         * @return number
         */
        public function getErrorNum()
        {
            return $this->error_num;
        }
        
        /**
         * 获取错误消息
         * @return string
         */
        public function getErrorMessage()
        {
            $this->setErrorMessage();
            return $this->error_message;
        }
        /**
         *
         * @param string $sql 预执行的SQL语句
         * @return number 返回处理的条数
         */
        public function execSQL($sql)
        {
            return $this->dbh->exec($sql);
        }
        
        
    
        /**
         * 默认返回数组， $type=true返回对象
         * 从结果集中获取下一行
         * 前面需要执行了单挑结果集才可使用
         *
         * @return multitype: 返回一条数据
         */
        public function getNextLineData()
        {
            // 设置结果
            $this->getTypeData(false);
            if (!$this->result)
            {
                $this->error_num = -1;
                return false;
            }
            
            // 如果没有记录则返回false;
            return $this->result;
        }
        
        /**
         * 不绑定参数："insert into `message`(`user_name`, `time`, `notes`) values('iiiiiiiiiii', '2016-1-1', ':notes')
         * 绑定参数 ：
         *
         * @param string $sql 预执行的SQL语句
         * @param array $param SQL参数绑定
         * @return bool 返回执行是否成功
         */
        public function noneQuery($sql, $param = array())
        {
            // 绑定参数 并执行 返回的结果集在 $this->statement
            if (!$this->paramBindExec($sql, $param))
            {
                $this->error_num = -2;
                return false;
            }
            
            return true;
        }
    
        /**
         * 如果没有记录则返回false;
         *
         * @param string $sql 预执行的SQL语句
         * @param string $param SQL参数绑定
         * @param bool $results 根据参数返回数组或者对象
         * @return mixed 返回结果集
         */
        public function query($sql, $param = array(), $results = false)
        {
            // 绑定参数 并执行 返回的结果集在 $this->statement
            if (!$this->paramBindExec($sql, $param))
            {
                $this->error_num = -2;
                return false;
            }
            
            // 设置结果
            $this->getTypeData($results);
            if (!$this->result)
            {
                $this->error_num = -1;
                return false;
            }
            
            // 如果没有记录则返回false;
            return $this->result;
        }
        
        /**
         * 第二个参数格式必须与第一个参数对应
         * $db->paramBindExec("select user_name, notes from message where user_name=:user_name", array(':user_name'=>'guest'), false);
         *
         * @param string $sql 预执行的SQL语句
         * @param array $param SQL参数绑定
         */
        private function paramBindExec($sql, $param)
        {
            try
            {
                $this->statement = $this->dbh->prepare($sql);
        
                // 绑定参数
                if (!empty($param))
                {
                    foreach ($param as $key => $value)
                    {
                        $this->statement->bindParam($key, $param[$key]);
                    }
                }
        
                // 执行预处理语句 成功返回true 失败返回false
                // 绑定的值不能超过指定的个数。如果在 input_parameters 中存在比 PDO::prepare() 预处理的SQL 指定的多的键名，则此语句将会失败并发出一个错误。
                return $this->statement->execute();
            }
            catch (PDOException $e)
            {
                $this->error_num = $e->getCode();
                $this->error_message =  $e->getMessage();
                
                return false;
            }
        }
    
        /**
         * 根据参数返回数组或者对象
         *
         * @param bool $type
         */
        private function getTypeData($results)
        {
            if ($results)
            {
                $this->result = $this->statement->fetchAll(PDO::FETCH_ASSOC);
            }
            else
            {
                $this->result = $this->statement->fetch(PDO::FETCH_ASSOC);
            }
        }
    
        /**
         * 释放数据库句柄
         */
        public function __destruct()
        {
            $this->dbh = null;
        }
        
        private function setErrorMessage()
        {
            switch ($this->error_num)
            {
                case 0:
                    $this->error_message = "没有错误，不要乱获取，等下就抛个异常给你";
                    break;
                case -1:
                    $this->error_message = "没有数据获取了";
                    break;
                case -2:
                    $this->error_message = "你的SQL语句有错误";
            }
        }
    }
    