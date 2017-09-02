<link rel="stylesheet" href="http://cdn.bootcss.com/bootstrap/3.3.0/css/bootstrap.min.css">
<script src="http://cdn.bootcss.com/jquery/1.11.1/jquery.min.js"></script>
<script src="http://cdn.bootcss.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
<?php
    require 'DB.php';

    $config = [
        'host' => 'localhost',
        'database' => 'test',
        'username' => 'root',
        'password' => 'root',
        'charset' => 'utf8',
    ];



    try
    {
        DB::initConfig($config);

        $pages =  $pages = DB::table('user')
            ->where('money', '<', 0)
            ->paginate(10);


        echo '<pre>';
        var_dump($pages);exit();


        echo '<table class="table">';
        foreach ($pages->data as $k=>$v)
        {
            echo <<<html
                <tr>
                    <td>{$v['id']}</td>
                    <td>{$v['user']}</td>
                    <td>{$v['money']}</td>
                </tr>
html;

        }

        echo '</table>';
        echo $pages->links;
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }


