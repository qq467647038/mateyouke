<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/22 0022
 * Time: 下午 5:11
 */
/**
 * @func 返回用户的姓名和电话
 * @param $uid用户id
 */
function getusernumber($uid){
    $users = db('member')->where(['id'=>$uid])->find();
    $name = '';
    if($users['user_name']){
        $name=$users['user_name'].'-';
    }
    return $name.$users['phone'];
}

/**
 * 俩个时间戳相差多少
 * @param $begin_time
 * @param $end_time
 * @return array
 */
function timediff($begin_time,$end_time){
    if($begin_time < $end_time){
        $starttime = $begin_time;
        $endtime = $end_time;
    }else{
        $starttime = $end_time;
        $endtime = $begin_time;
    }
    //计算天数
    $timediff = $endtime-$starttime;
    $days = intval($timediff/86400);
    //计算小时数
    $remain = $timediff%86400;
    $hours = intval($remain/3600);
    //计算分钟数
    $remain = $remain%3600;
    $mins = intval($remain/60);
    //计算秒数
    $secs = $remain%60;
    $res = array("day" => $days,"hour" => $hours,"min" => $mins,"sec" => $secs);
    return $res;
}