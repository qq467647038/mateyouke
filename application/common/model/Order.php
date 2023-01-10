<?php
namespace app\common\model;
use think\Model;
use think\Db;

class Order extends Model
{
    public function goods()
    {
        return $this->hasMany('OrderGoods','order_id','id');
    }

    /**
     * 通过订单号查询订单信息
     *
     * @param [type] $ordernumber
     * @return void
     */
    public static function findOrderByOrderNumber($ordernumber)
    {
        return self::with('goods')->where(['ordernumber'=>$ordernumber])->find();
    }
}