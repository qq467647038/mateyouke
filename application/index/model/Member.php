<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/8 0008
 * Time: 15:10
 */
namespace app\apicloud\model;
use think\Cache;
use think\Model;

class Member extends Model
{
    /**
     * 用户详情
     * @param $key 键值
     * @param $val 值
     * @return object
     * @author:Damow
     */
    public function getUser($key,$val){
        return db('member')->where([$key=>$val])->field('user_name,phone,headimgurl,integral')->find();
    }

    /**
     * 添加签到日志，但不增加积分
     * @param $num=1 连续签到
     * @return object
     * @author:Damow
     */
    public function addLog($integral,$content,$num=0,$days=0){
        $tomouth    = date('Y-m', time());
        $data   = [
            'time'      => time(),
            'user_id'   => Cache::get('user_id'),
            'credit'    => $integral,
            'log'       => $content,
        ];
        $where   =[
            'signdate'  => $tomouth,
            'user_id'   => $data['user_id'],
        ];
        $info   = db('sign_user')->where($where)->find();
        if($num==1){
            $data['type'] = 1;
            $data['day'] = $days;
            db('sign_records')->insert($data);
            empty($info)?db('sign_user')->insert(['user_id'=>Cache::get('user_id'),'signdate'=>$tomouth,'sum'=>1]):db('sign_user')->where($where)->setInc('sum');
        }else{
            //新增日志
            db('sign_records')->insert($data);
            //增加签到天数
            empty($info)?db('sign_user')->insert(['user_id'=>Cache::get('user_id'),'signdate'=>$tomouth,'orderday'=>1]):db('sign_user')->where($where)->setInc('orderday');
        }
    }
}