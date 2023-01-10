<?php
namespace app\apicloud\model;
use think\Cache;
use think\Model;

class SignSet extends Model
{

    /**
     * 获取签到的各种信息
     * @param
     * @return object
     * @author:Damow
     */
    public function getSign($date = NULL)
    {
        $condition = [];
        //本月签到的时间
        $month_start = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $month_end = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        $condition['time']      = ['between',[$month_start,$month_end]];
        $condition['user_id']   = Cache::get('user_id');
        $condition['type']      = 0;
        $records = db('sign_records')->where($condition)->order('time desc')->select();
        if(!empty($records)){
            $orderindex = 1;
            foreach ($records as $key=>$item){
                $dday = date('d', $item['time']);
                $pday = date('d', isset($records[$key + 1]['time']) ? $records[$key + 1]['time'] : 0);

                if (($dday - $pday) == 1) {
                    ++$orderindex; 
                }
                $records[$key]['time']    = date('Y-n-j', $item['time']);
            }
        }else{
            $orderindex = 0;
        }

        //今日是否签到
        $sign_list = array_index_value($records,'id','time');
        $boole     = in_array(date('Y-n-j',time()),$sign_list);

        $data      = array('total' => empty($records)?0:count($records),'continuous'=>$orderindex,'sign_list'=>array_values($sign_list),'today'=>$boole);
        return $data;
    }




}
