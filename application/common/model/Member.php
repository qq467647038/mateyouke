<?php
namespace app\common\model;
use think\Model;
use think\Db;

class Member extends Model
{

    public function soldierType()
    {
        return $this->hasOne('SoldierInfo','soldier_type','soldier');
    }

    public static function queryPage($where,$page)
    {
        return self::with(['soldierType'])->where($where)->order('id desc')->paginate($page);
    }

    /**
     * 通过id修改信息
     *
     * @param [type] $id
     * @param [type] $array
     * @return void
     */
    public static function updateById($id,$array)
    {
        return self::where('id',$id)->update($array);
    }

    /**
     * 通过用户id获取信息
     */
    public static function findById($id)
    {
        return self::where('id',$id)->find();
    }

    /**
     * 通过店铺id搜索用户
     *
     * @param [type] $shop_id
     * @return void
     */
    public static function findByShopId($shop_id)
    {
        return self::where('shop_id',$shop_id)->find();
    }

    public static function add($data)
    {
        $data['regtime'] = time();
        return self::create($data);
    }

    public static function findByUnId($unid)
    {
        return self::where('unionid',$unid)->find();
    }

    /**
     * 获取公众号openid
     *
     * @param [type] $openid
     * @return void
     */
    public static function findByWxOpenId($openid)
    {
        if(empty($openid))
        {
            return [];
        }

        return self::where('wx_openid',$openid)->find();
    }
}