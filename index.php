<?php
    require 'DB.php';

    $config = require 'conf.php';


    try
    {
        DB::initConfig($config);

        $res = DB::table('user')->select(['user', 'pwd'])->where('money', '=', '0')->AndWhere('login_count', '>', '0')->get();

        var_dump($res);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }


