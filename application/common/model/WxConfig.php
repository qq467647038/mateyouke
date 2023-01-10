<?php
namespace app\common\model;
use think\Model;
use think\Db;

class WxConfig extends Model
{
    public static function updateData($data){
        return self::where($data['id'])->update($data);
    }
}