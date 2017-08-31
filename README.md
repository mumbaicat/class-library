 WaitMoonMan/ClassLibrary
===================================  
 ![gps](https://avatars0.githubusercontent.com/u/28035971?v=3&s=460 "gps")  
  
### 说明
>
> 查询构造器   DB.php  <br />
> 文件上传类   FileUpload.php  <br />
> 数据库操作   DataBase.php
> 
### 查询构造器
```php
<?php
     require 'DB.php';
     
     
     $config = [
                   'host' => 'localhost',
                   'database' => 'test',
                   'username' => 'root',
                   'password' => 'root',
                   'charset' => 'utf8',
               ];

    // 初始化配置
     DB::initConfig($config);
    
     
     // 初始化配置之后，就可以进行 CURD 了
     // 增
     DB::table('user')->insert(['user' => 'admin', 'pwd' => 'admin']);
     
     // 删
     DB::table('user')->where('user', '=', 'admin')->delete();
      
     // 查
     DB::find(1);
     DB::table('user')
         ->select(['user', 'pwd'])
         ->where('money', '>', '-1')
         ->AndWhere('login_count', '>', '-1')
         ->orderBy('money', 'desc')
         ->limit(2)
         ->get();
     
     // 改
     DB::table('user')->where('user', '=', 'admin')->update(['pwd' => 'admin999', 'money' => 999]);
    
```
### 文件上传类用法
```php
<?php
    require "FileUpload.php";

    $setting = [
                'file_path' => 'upload', 
                'is_allow_all' => true,     // 允许所有文件类型
                'is_rand_name' => true,     // 是否随机名字
                'max_size' => '1000000000'
               ];
    $upload = new FileUpload($setting);

    // 文件核心上传  参数是表单元素的 name
    if (!$upload->uploadFile("file"))
    {
        // 失败获取错误
        echo $upload->getErrorMessage();
    }
    else
    {
        // 成功获取新文件名
        echo $upload->getNewFileName();
    }
```
### 数据库操作类用法
```php
<?php
    require "DataBase.php";	

    // 参数和 PDO 是一样的
    $db = new DataBase(DSN, DB_USER, DB_PASSWORD);

    // 测试数据
    $id = 1;
    $age = 20;
    
    // 非查询
    $sql = "select `name`, `pwd` from `test` where `id`=:id and age>:age";
    $data = [
        ':id'  => $id,
        ':age' => $age
    ];


    // 第三个参数为 true 则返回所有数据而不是一条
    $result = $db->query($sql, $data);
     
    if ($result)
    {
        echo "success";
    }
    else
    {
        // 获取错误消息
        $db->getErrorMessage();
    }

```
