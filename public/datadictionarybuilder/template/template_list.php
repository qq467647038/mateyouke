<?php defined('ROOT') or exit('hacker'); ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
    <style>
        body{font-family: 微软雅黑;font-size:18px;}
        .main{width:1000px;margin:0 auto;}
        .database_list{float:left;width:500px;}
        .database_cache{float:left;width:500px;}
    </style>
</head>
<body>
    <h1 align="center">数据字典生成工具</h1>
    <div class="main">
        <table class="database_list" border="1" cellpadding="1" cellspacing="0">
            <tr>
                <th colspan="2">
                    数据库
                </th>
            </tr>
            <?php foreach($database_list as $item){?>
            <tr>
                <td><?=$item?></td>
                <td>
                    <a href="index.php?act=gen&database=<?=$item?>">生成</a>
                </td>
            </tr>
            <?php } ?>
        </table>
        <table class="database_cache" border="1" cellpadding="1" cellspacing="0">
            <tr>
                <th>生成的文件数据字典</th>
                <th>操作 <a href="index.php?act=clearAll">清除所有</a></th>
            </tr>
            <?php foreach($file_list as $item){ ?>
            <tr>
                <td><?=$item?></td>
                <td>
                    <a href="index.php?act=show&file=<?=$item?>">查看</a>
                    <a href="index.php?act=download&file=<?=$item?>">下载</a>
                    <a href="index.php?act=clear&file=<?=$item?>">清除</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>