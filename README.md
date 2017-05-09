 WaitMoonMan/ClassLibrary
===================================  
 ![gps](https://avatars0.githubusercontent.com/u/28035971?v=3&s=460 "gps")  
  
### 说明
> 
> 文件上传类   fileupload.class.php  <br />
> 数据库操作   db.class.php
> 

### 文件上传类用法
```php
<?php
    require "class/fileupload.class.php";

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
    require "class/db.class.php";
    
    // 参数和 PDO 是一样的
    $db = new DB(DSN, DB_USER, DB_PASSWORD);

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
