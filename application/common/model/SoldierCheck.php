<?php
namespace app\common\model;
use think\Model;
use think\Db;

class SoldierCheck extends Model
{

    public function member()
    {
        return $this->hasOne('Member','id','user_id');
    }

    public function soldierInfo()
    {
        return $this->hasOne('SoldierInfo','soldier_type','soldier_type');
    }

    public static function findBySoldierType($type)
    {
        return self::where('soldier_type',$type)->where('status',1)->select();
    }

    public static function countBySoldierType($type)
    {
        return self::where('soldier_type',$type)->where('status',1)->count();
    }

    /**
     * 通过用户ID查询
     *
     * @param [type] $user_id
     * @return void
     */
    public static function findByUserId($user_id)
    {
        return self::where('user_id',$user_id)->find();
    }

    /**
     * 添加
     *
     * @param [type] $data
     * @return void
     */
    public static function add($data)
    {
        $data['c_time'] = time();
        return self::create($data);
    }

    /**
     * 通过卡ID查询
     *
     * @param [type] $card_id
     * @return void
     */
    public static function findByCardId($card_id)
    {
        return self::with(['member'])->where('card_id',$card_id)->find();
    }

    /**
     * 通过ID查询
     *
     * @param [type] $id
     * @return void
     */
    public static function findById($id)
    {
        return self::with(['member'])->where('id',$id)->find();
    }

    /**
     * 修改
     *
     * @param [type] $data
     * @return void
     */
    public static function edit($data)
    {
        $data['status'] = 0;
        return self::where(['id'=>$data['id']])->update($data);
    }

    /**
     * 条件分页查询
     *
     * @param [type] $where
     * @param [type] $page
     * @param [type] $size
     * @return void
     */
    public static function queryPage($where,$size)
    {
        return self::with(['member','soldierInfo'])->where($where)->paginate($size);
    }

    /**
     * 条件查看总数
     *
     * @param [type] $where
     * @return void
     */
    public static function queryCount($where)
    {
        return self::where($where)->count();
    }

    /**
     * 通过
     *
     * @param [type] $id
     * @return void
     */
    public static function pass($id)
    {
        return self::where('id',$id)->update(['status'=>1]);
    }

    /**
     * 拒绝
     *
     * @param [type] $id
     * @return void
     */
    public static function refuse($id)
    {
        return self::where('id',$id)->update(['status'=>2]);
    }

}