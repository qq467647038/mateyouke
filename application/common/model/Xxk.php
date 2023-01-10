<?php
namespace app\common\model;
use think\Model;
use think\Db;

class Xxk extends Model
{
    public function add($data)
    {
        $data['create_time'] = date("Y-m-d H:i:s",time());
        $data['bind_time'] = date("Y-m-d H:i:s",time());
        return self::create($data);
    }

    public static function updateByCardId($card_id,$user_id)
    {
        return self::where('card_id',$card_id)->update(['user_id'=>$user_id]);
    }

    public static function edit($data)
    {
        return self::where('card_id',$data['card_id'])->update($data);
    }

    public static function findByCardId($card_id)
    {
        return self::where('card_id',$card_id)->find();
    }

    public static function queryCount($where)
    {
        return self::where($where)->count();
    }
}