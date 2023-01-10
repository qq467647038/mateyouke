<?php

/**
 * 公共函数库
 */


/**
 * 展示信息
 * @param  string $msg 提示信息
 * @param  string $url 跳转地址
 */
function show_msg($msg,$url='')
{
    echo '<script>';    
    echo "alert('$msg');";

    if (empty($url)) {
        echo 'hisotry.go(-1);';
    }else{
        echo "window.location.href='$url'";
    }

    echo '</script>';
    exit;
}

function time_format($file)
{
    $temp=explode('.',$file);

    return date('Y-m-d H:i:s',$temp[0]);
}