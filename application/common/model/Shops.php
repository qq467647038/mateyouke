<?php
namespace app\common\model;
use think\Model;
use think\Db;

class Shops extends Model
{

    /**
     * 通过店铺id查询
     *
     * @param [type] $shop_id
     * @return void
     */
    public static function findByShopId($shop_id)
    {
        return self::where('id',$shop_id)->find();
    }
}