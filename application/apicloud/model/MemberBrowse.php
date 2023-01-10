<?php
/**
 * Created by PhpStorm.
 * @anthor: Pupil_Chen
 * Date: 2020/9/22 0022
 * Time: 10:27
 */

namespace app\apicloud\model;


use think\Db;
use think\Model;
use app\apicloud\model\Goods as GoodsModel;
class MemberBrowse extends Model
{
    // 是否需要自动写入时间戳 如果设置为字符串 则表示时间字段的类型
    protected $autoWriteTimestamp = true;

    public function GoodsInfo(){
        return $this->hasOne('Goods','id','id_goods');
    }

    /**
     * @function添加足迹
     * @param string $id
     * @param $cid
     * @author Feifan.Chen <1057286925@qq.com>
     * @return array
     */
    public static function addBrowse($id = '',$cid){

        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $start = mktime(0,0,0,$month,$day,$year);//当天开始时间戳
        $end= mktime(23,59,59,$month,$day,$year);//当天结束时间戳
        //查找当天是否有添加记录，有的话 数量+1 没有的话 添加记录
        $browse = self::where(['id_customer'=>$cid,'id_goods'=>$id])
            ->whereBetween('create_time',[$start,$end])

            ->find();

        if ($browse)
        {
            self::where(['id_customer'=>$cid,'id_goods'=>$id])
                ->whereBetween('create_time',[$start,$end])
                ->inc('number')
                ->update();
        }
        else
        {
            $data = [
                'id_customer'   =>  $cid,
                'id_goods'      =>  $id,
                'number'        =>  1
            ];
            self::create($data);
        }
        return array('status'=>200,'mess'=>'添加成功','data'=>[]);
    }


    /**
     * @function获取足迹列表
     * @param $cid
     * @author Feifan.Chen <1057286925@qq.com>
     * @return mixed
     */
    public static function browerList($cid)
    {
        $brower_data = self::query(
        "SELECT
                    CASE
                WHEN times = date_format(now(),'%Y-%m-%d') THEN
                    '今天'
                ELSE
                    times
                END AS time_data,
                 goods_list
                FROM
                    (
                        SELECT
                            FROM_UNIXTIME(create_time, '%Y-%m-%d') AS times,
                            GROUP_CONCAT(id_goods ORDER BY update_time DESC) AS goods_list
                        FROM
                            sp_member_browse
                        GROUP BY
                            FROM_UNIXTIME(create_time, '%Y-%m-%d')
                    
                    ) a
                ORDER BY time_data desc"
        );

        foreach ($brower_data as $k=>&$v){

            $v['goods_list'] = GoodsModel::field('id,goods_name,thumb_url,market_price')->whereIn('id',$v['goods_list'])->select();
        }

        return $brower_data;
    }


}