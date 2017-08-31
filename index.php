<?php
    require 'DB.php';

    $config = require 'conf.php';

    echo '<pre>';


    try
    {
        DB::initConfig($config);


        $res = DB::table('user')
                    ->select(['user', 'pwd'])
                    ->where('money', '>', '-1')
                    ->AndWhere('login_count', '>', '-1')
                    ->orderBy('money', 'desc')
                    ->limit(2)
                    ->get();

        var_dump($res);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }


