<?php
namespace app\common\model;
use think\Model;
use think\Db;

class Goods extends Model
{
    public static function findById($id)
    {
        return self::where('id',$id)->find();
    }
}